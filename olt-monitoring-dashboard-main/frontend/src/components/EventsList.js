import React from 'react';
import {
  List,
  ListItem,
  ListItemText,
  ListItemIcon,
  Typography,
  Box,
  Chip,
  CircularProgress,
  Alert,
} from '@mui/material';
import {
  CheckCircle as OnlineIcon,
  Error as ErrorIcon,
  Warning as WarningIcon,
  SignalWifiOff as OfflineIcon,
  Info as InfoIcon,
} from '@mui/icons-material';
import { useQuery } from 'react-query';
import { dashboardApi } from '../services/api';

// Event severity icons
const severityIcons = {
  info: <InfoIcon color="info" />,
  warning: <WarningIcon color="warning" />,
  error: <ErrorIcon color="error" />,
  critical: <ErrorIcon color="error" />,
};

const eventTypeLabels = {
  ont_online: 'ONT Online',
  ont_offline: 'ONT Offline',
  ont_los: 'ONT LOS',
  power_warning: 'Power Warning',
  distance_warning: 'Distance Warning',
  olt_offline: 'OLT Offline',
  olt_online: 'OLT Online',
};

function EventsList({ limit = 10, compact = false }) {
  const { data, isLoading, error } = useQuery(
    ['dashboard-events', { hours: 24 }],
    () => dashboardApi.getEvents(24),
    {
      refetchInterval: 30000,
    }
  );

  if (isLoading) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" p={2}>
        <CircularProgress size={24} />
      </Box>
    );
  }

  if (error) {
    return (
      <Alert severity="error" sx={{ m: 1 }}>
        Error loading events
      </Alert>
    );
  }

  const events = data?.data?.slice(0, limit) || [];

  if (events.length === 0) {
    return (
      <Box p={2} textAlign="center">
        <Typography variant="body2" color="textSecondary">
          No recent events
        </Typography>
      </Box>
    );
  }

  return (
    <List dense={compact} sx={{ p: 0 }}>
      {events.map((event) => (
        <ListItem key={event.id} divider>
          <ListItemIcon sx={{ minWidth: compact ? 32 : 56 }}>
            {severityIcons[event.severity] || severityIcons.info}
          </ListItemIcon>
          <ListItemText
            primary={
              <Box>
                <Typography 
                  variant={compact ? "body2" : "body1"} 
                  component="div"
                >
                  {event.message}
                </Typography>
                {!compact && (
                  <Box display="flex" gap={1} mt={0.5}>
                    <Chip
                      label={eventTypeLabels[event.event_type] || event.event_type}
                      size="small"
                      variant="outlined"
                    />
                    <Chip
                      label={event.severity.toUpperCase()}
                      size="small"
                      color={
                        event.severity === 'critical' || event.severity === 'error'
                          ? 'error'
                          : event.severity === 'warning'
                          ? 'warning'
                          : 'info'
                      }
                    />
                  </Box>
                )}
              </Box>
            }
            secondary={
              <Box>
                <Typography variant="caption" color="textSecondary">
                  {event.olt_name}
                  {event.customer_name && ` • ${event.customer_name}`}
                  {event.port && ` • ${event.port}`}
                </Typography>
                <br />
                <Typography variant="caption" color="textSecondary">
                  {new Date(event.created_at).toLocaleString('id-ID')}
                </Typography>
              </Box>
            }
          />
        </ListItem>
      ))}
    </List>
  );
}

export default EventsList;