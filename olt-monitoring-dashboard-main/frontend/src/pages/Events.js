import React, { useState } from 'react';
import {
  Container,
  Typography,
  Card,
  CardContent,
  Grid,
  Box,
  Tabs,
  Tab,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Button,
  Chip,
} from '@mui/material';
import {
  Event as EventIcon,
  TrendingUp as TrendingUpIcon,
  Refresh as RefreshIcon,
} from '@mui/icons-material';
import { DataGrid, GridToolbarContainer, GridToolbarExport } from '@mui/x-data-grid';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { useQuery } from 'react-query';
import { useSnackbar } from 'notistack';

import { eventsApi } from '../services/api';

function TabPanel({ children, value, index, ...other }) {
  return (
    <div
      role="tabpanel"
      hidden={value !== index}
      id={`events-tabpanel-${index}`}
      aria-labelledby={`events-tab-${index}`}
      {...other}
    >
      {value === index && <Box sx={{ pt: 3 }}>{children}</Box>}
    </div>
  );
}

function CustomToolbar() {
  return (
    <GridToolbarContainer>
      <GridToolbarExport />
    </GridToolbarContainer>
  );
}

function Events() {
  const [tabValue, setTabValue] = useState(0);
  const [timeFilter, setTimeFilter] = useState(24);
  const [severityFilter, setSeverityFilter] = useState('');
  const [eventTypeFilter, setEventTypeFilter] = useState('');
  const { enqueueSnackbar } = useSnackbar();

  const { data: events, isLoading: eventsLoading, refetch: refetchEvents } = useQuery(
    ['events', { hours: timeFilter, severity: severityFilter, event_type: eventTypeFilter }],
    () => eventsApi.getAll({ 
      hours: timeFilter, 
      severity: severityFilter, 
      event_type: eventTypeFilter,
      limit: 100
    }),
    {
      refetchInterval: 30000,
      onError: (error) => {
        enqueueSnackbar('Error loading events', { variant: 'error' });
      },
    }
  );

  const { data: stats } = useQuery(
    ['event-stats', timeFilter],
    () => eventsApi.getStats(timeFilter),
    {
      refetchInterval: 60000,
    }
  );

  const eventTypeLabels = {
    ont_online: 'ONT Online',
    ont_offline: 'ONT Offline',
    ont_los: 'ONT LOS',
    power_warning: 'Power Warning',
    distance_warning: 'Distance Warning',
    olt_offline: 'OLT Offline',
    olt_online: 'OLT Online',
  };

  const getSeverityColor = (severity) => {
    switch (severity) {
      case 'critical': return 'error';
      case 'error': return 'error';
      case 'warning': return 'warning';
      case 'info': return 'info';
      default: return 'default';
    }
  };

  const columns = [
    {
      field: 'created_at',
      headerName: 'Time',
      width: 150,
      renderCell: (params) => {
        const date = new Date(params.value);
        return date.toLocaleString('id-ID');
      },
    },
    {
      field: 'severity',
      headerName: 'Severity',
      width: 100,
      renderCell: (params) => (
        <Chip
          label={params.value.toUpperCase()}
          size="small"
          color={getSeverityColor(params.value)}
        />
      ),
    },
    {
      field: 'event_type',
      headerName: 'Event Type',
      width: 150,
      renderCell: (params) => (
        <Chip
          label={eventTypeLabels[params.value] || params.value}
          size="small"
          variant="outlined"
        />
      ),
    },
    {
      field: 'olt_name',
      headerName: 'OLT',
      width: 120,
      renderCell: (params) => (
        <Box>
          <div>{params.value}</div>
          {params.row.olt_location && (
            <div style={{ fontSize: '0.8em', color: '#666' }}>
              {params.row.olt_location}
            </div>
          )}
        </Box>
      ),
    },
    {
      field: 'customer_info',
      headerName: 'Customer/Port',
      width: 150,
      renderCell: (params) => (
        <Box>
          <div>{params.row.customer_name || 'Unknown'}</div>
          {params.row.port && (
            <div style={{ fontSize: '0.8em', color: '#666' }}>
              {params.row.port}
            </div>
          )}
        </Box>
      ),
    },
    {
      field: 'message',
      headerName: 'Message',
      width: 300,
      flex: 1,
    },
  ];

  const rows = events?.data?.data || [];

  // Prepare chart data
  const chartData = stats?.data?.hourly?.map(item => ({
    hour: new Date(item.hour).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
    count: item.count
  })) || [];

  return (
    <Container maxWidth="xl">
      <Box display="flex" justifyContent="space-between" alignItems="center" mb={3}>
        <Typography variant="h4">
          Events & Logs
        </Typography>
        <Button
          variant="outlined"
          startIcon={<RefreshIcon />}
          onClick={refetchEvents}
        >
          Refresh
        </Button>
      </Box>

      <Card>
        <CardContent>
          <Tabs value={tabValue} onChange={(e, v) => setTabValue(v)} aria-label="events tabs">
            <Tab label="Event List" icon={<EventIcon />} />
            <Tab label="Statistics" icon={<TrendingUpIcon />} />
          </Tabs>

          <TabPanel value={tabValue} index={0}>
            {/* Filters */}
            <Grid container spacing={2} sx={{ mb: 2 }}>
              <Grid item xs={12} md={3}>
                <FormControl fullWidth size="small">
                  <InputLabel>Time Range</InputLabel>
                  <Select
                    value={timeFilter}
                    label="Time Range"
                    onChange={(e) => setTimeFilter(e.target.value)}
                  >
                    <MenuItem value={1}>Last 1 Hour</MenuItem>
                    <MenuItem value={6}>Last 6 Hours</MenuItem>
                    <MenuItem value={24}>Last 24 Hours</MenuItem>
                    <MenuItem value={72}>Last 3 Days</MenuItem>
                    <MenuItem value={168}>Last 7 Days</MenuItem>
                  </Select>
                </FormControl>
              </Grid>
              <Grid item xs={12} md={3}>
                <FormControl fullWidth size="small">
                  <InputLabel>Severity</InputLabel>
                  <Select
                    value={severityFilter}
                    label="Severity"
                    onChange={(e) => setSeverityFilter(e.target.value)}
                  >
                    <MenuItem value="">All Severities</MenuItem>
                    <MenuItem value="critical">Critical</MenuItem>
                    <MenuItem value="error">Error</MenuItem>
                    <MenuItem value="warning">Warning</MenuItem>
                    <MenuItem value="info">Info</MenuItem>
                  </Select>
                </FormControl>
              </Grid>
              <Grid item xs={12} md={3}>
                <FormControl fullWidth size="small">
                  <InputLabel>Event Type</InputLabel>
                  <Select
                    value={eventTypeFilter}
                    label="Event Type"
                    onChange={(e) => setEventTypeFilter(e.target.value)}
                  >
                    <MenuItem value="">All Types</MenuItem>
                    <MenuItem value="ont_offline">ONT Offline</MenuItem>
                    <MenuItem value="ont_online">ONT Online</MenuItem>
                    <MenuItem value="ont_los">ONT LOS</MenuItem>
                    <MenuItem value="power_warning">Power Warning</MenuItem>
                    <MenuItem value="distance_warning">Distance Warning</MenuItem>
                    <MenuItem value="olt_offline">OLT Offline</MenuItem>
                  </Select>
                </FormControl>
              </Grid>
            </Grid>

            {/* Events Table */}
            <Box sx={{ height: 500, width: '100%' }}>
              <DataGrid
                rows={rows}
                columns={columns}
                pageSize={25}
                rowsPerPageOptions={[10, 25, 50, 100]}
                loading={eventsLoading}
                disableSelectionOnClick
                components={{
                  Toolbar: CustomToolbar,
                }}
                sx={{
                  '& .MuiDataGrid-cell': {
                    borderBottom: '1px solid #f0f0f0',
                  },
                  '& .MuiDataGrid-row:hover': {
                    backgroundColor: '#f5f5f5',
                  },
                }}
              />
            </Box>
          </TabPanel>

          <TabPanel value={tabValue} index={1}>
            <Grid container spacing={3}>
              {/* Event Type Summary */}
              <Grid item xs={12} md={6}>
                <Card variant="outlined">
                  <CardContent>
                    <Typography variant="h6" gutterBottom>
                      Events by Type (Last {timeFilter}h)
                    </Typography>
                    <Box>
                      {stats?.data?.event_types?.map((item) => (
                        <Box key={`${item.event_type}-${item.severity}`} display="flex" justifyContent="space-between" alignItems="center" mb={1}>
                          <Box display="flex" gap={1}>
                            <Chip
                              label={eventTypeLabels[item.event_type] || item.event_type}
                              size="small"
                              variant="outlined"
                            />
                            <Chip
                              label={item.severity.toUpperCase()}
                              size="small"
                              color={getSeverityColor(item.severity)}
                            />
                          </Box>
                          <Typography variant="body2" fontWeight="bold">
                            {item.count}
                          </Typography>
                        </Box>
                      ))}
                    </Box>
                  </CardContent>
                </Card>
              </Grid>

              {/* Hourly Chart */}
              <Grid item xs={12} md={6}>
                <Card variant="outlined">
                  <CardContent>
                    <Typography variant="h6" gutterBottom>
                      Events Over Time
                    </Typography>
                    <Box sx={{ height: 300 }}>
                      <ResponsiveContainer width="100%" height="100%">
                        <BarChart data={chartData}>
                          <CartesianGrid strokeDasharray="3 3" />
                          <XAxis 
                            dataKey="hour" 
                            fontSize={12}
                            angle={-45}
                            textAnchor="end"
                            height={60}
                          />
                          <YAxis fontSize={12} />
                          <Tooltip />
                          <Bar dataKey="count" fill="#1976d2" />
                        </BarChart>
                      </ResponsiveContainer>
                    </Box>
                  </CardContent>
                </Card>
              </Grid>
            </Grid>
          </TabPanel>
        </CardContent>
      </Card>
    </Container>
  );
}

export default Events;