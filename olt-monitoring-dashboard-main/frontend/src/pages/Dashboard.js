import React from 'react';
import { 
  Grid, 
  Card, 
  CardContent, 
  Typography, 
  Box,
  CircularProgress,
  Alert,
  Chip,
  Divider,
} from '@mui/material';
import { 
  Router as RouterIcon,
  People as PeopleIcon,
  Warning as WarningIcon,
  CheckCircle as CheckCircleIcon,
  Error as ErrorIcon,
  SignalWifiOff as OfflineIcon,
} from '@mui/icons-material';
import { useQuery } from 'react-query';
import { useSnackbar } from 'notistack';

import { dashboardApi } from '../services/api';
import ONTTable from '../components/ONTTable';
import EventsList from '../components/EventsList';
import PowerChart from '../components/PowerChart';

function Dashboard() {
  const { enqueueSnackbar } = useSnackbar();

  const { data: summary, isLoading: summaryLoading, error: summaryError } = useQuery(
    'dashboard-summary',
    dashboardApi.getSummary,
    {
      refetchInterval: 30000,
      onError: (error) => {
        console.error('Dashboard summary error:', error);
        enqueueSnackbar('Error loading dashboard summary', { variant: 'error' });
      },
    }
  );

  const { data: powerStats } = useQuery(
    'power-stats',
    dashboardApi.getPowerStats,
    {
      refetchInterval: 60000,
    }
  );

  if (summaryLoading) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" minHeight="50vh">
        <CircularProgress size={60} />
      </Box>
    );
  }

  if (summaryError) {
    return (
      <Alert severity="error" sx={{ mb: 2 }}>
        Error loading dashboard data: {summaryError.message}
      </Alert>
    );
  }

  const summaryData = summary?.data || {};

  return (
    <Box>
      <Typography variant="h4" gutterBottom>
        Dashboard
      </Typography>
      
      {/* Summary Cards */}
      <Grid container spacing={3} sx={{ mb: 3 }}>
        <Grid item xs={12} sm={6} md={3}>
          <Card>
            <CardContent>
              <Box display="flex" alignItems="center" mb={1}>
                <RouterIcon color="primary" sx={{ mr: 1 }} />
                <Typography color="textSecondary" gutterBottom>
                  OLT Status
                </Typography>
              </Box>
              <Typography variant="h4" component="div">
                {summaryData.olts?.online_olts || 0}/{summaryData.olts?.total_olts || 0}
              </Typography>
              <Typography variant="body2" color="textSecondary">
                Online OLTs
              </Typography>
            </CardContent>
          </Card>
        </Grid>

        <Grid item xs={12} sm={6} md={3}>
          <Card>
            <CardContent>
              <Box display="flex" alignItems="center" mb={1}>
                <CheckCircleIcon color="success" sx={{ mr: 1 }} />
                <Typography color="textSecondary" gutterBottom>
                  ONT Online
                </Typography>
              </Box>
              <Typography variant="h4" component="div" color="success.main">
                {summaryData.onts?.online_onts || 0}
              </Typography>
              <Typography variant="body2" color="textSecondary">
                Active Connections
              </Typography>
            </CardContent>
          </Card>
        </Grid>

        <Grid item xs={12} sm={6} md={3}>
          <Card>
            <CardContent>
              <Box display="flex" alignItems="center" mb={1}>
                <OfflineIcon color="error" sx={{ mr: 1 }} />
                <Typography color="textSecondary" gutterBottom>
                  ONT Issues
                </Typography>
              </Box>
              <Typography variant="h4" component="div" color="error.main">
                {(summaryData.onts?.offline_onts || 0) + (summaryData.onts?.los_onts || 0)}
              </Typography>
              <Box display="flex" gap={1} mt={1}>
                <Chip 
                  label={`${summaryData.onts?.offline_onts || 0} Offline`} 
                  size="small" 
                  color="error"
                  variant="outlined"
                />
                <Chip 
                  label={`${summaryData.onts?.los_onts || 0} LOS`} 
                  size="small" 
                  color="warning"
                  variant="outlined"
                />
              </Box>
            </CardContent>
          </Card>
        </Grid>

        <Grid item xs={12} sm={6} md={3}>
          <Card>
            <CardContent>
              <Box display="flex" alignItems="center" mb={1}>
                <WarningIcon color="warning" sx={{ mr: 1 }} />
                <Typography color="textSecondary" gutterBottom>
                  Warnings
                </Typography>
              </Box>
              <Typography variant="h4" component="div" color="warning.main">
                {(summaryData.warnings?.power || 0) + (summaryData.warnings?.distance || 0)}
              </Typography>
              <Box display="flex" gap={1} mt={1}>
                <Chip 
                  label={`${summaryData.warnings?.power || 0} Power`} 
                  size="small" 
                  color="warning"
                  variant="outlined"
                />
                <Chip 
                  label={`${summaryData.warnings?.distance || 0} Distance`} 
                  size="small" 
                  color="warning"
                  variant="outlined"
                />
              </Box>
            </CardContent>
          </Card>
        </Grid>
      </Grid>

      <Grid container spacing={3}>
        {/* ONT Table */}
        <Grid item xs={12} lg={8}>
          <Card sx={{ height: '600px' }}>
            <CardContent sx={{ height: '100%', p: 0 }}>
              <Box p={2} pb={0}>
                <Typography variant="h6" gutterBottom>
                  ONT Status Overview
                </Typography>
              </Box>
              <Divider />
              <Box sx={{ height: 'calc(100% - 60px)' }}>
                <ONTTable />
              </Box>
            </CardContent>
          </Card>
        </Grid>

        {/* Right Panel */}
        <Grid item xs={12} lg={4}>
          <Grid container spacing={3}>
            {/* Power Chart */}
            <Grid item xs={12}>
              <Card sx={{ height: '280px' }}>
                <CardContent sx={{ height: '100%' }}>
                  <Typography variant="h6" gutterBottom>
                    Power Statistics
                  </Typography>
                  <Divider sx={{ mb: 2 }} />
                  <Box sx={{ height: 'calc(100% - 60px)' }}>
                    <PowerChart data={powerStats?.data || []} />
                  </Box>
                </CardContent>
              </Card>
            </Grid>

            {/* Recent Events */}
            <Grid item xs={12}>
              <Card sx={{ height: '300px' }}>
                <CardContent sx={{ height: '100%', p: 0 }}>
                  <Box p={2} pb={0}>
                    <Typography variant="h6" gutterBottom>
                      Recent Events
                    </Typography>
                  </Box>
                  <Divider />
                  <Box sx={{ height: 'calc(100% - 60px)', overflow: 'hidden' }}>
                    <EventsList limit={5} compact />
                  </Box>
                </CardContent>
              </Card>
            </Grid>
          </Grid>
        </Grid>
      </Grid>
    </Box>
  );
}

export default Dashboard;