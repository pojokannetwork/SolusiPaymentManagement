import React, { useState } from 'react';
import {
  DataGrid,
  GridToolbarContainer,
  GridToolbarFilterButton,
  GridToolbarExport,
  GridToolbarColumnsButton,
} from '@mui/x-data-grid';
import {
  Box,
  Chip,
  IconButton,
  Tooltip,
  TextField,
  MenuItem,
  FormControl,
  InputLabel,
  Select,
  Grid,
} from '@mui/material';
import {
  Refresh as RefreshIcon,
  Edit as EditIcon,
  SignalWifi4Bar as SignalIcon,
  SignalWifiOff as NoSignalIcon,
  Warning as WarningIcon,
} from '@mui/icons-material';
import { useQuery } from 'react-query';
import { useSnackbar } from 'notistack';
import { dashboardApi } from '../services/api';

// Custom toolbar
function CustomToolbar({ onRefresh, searchValue, onSearchChange, statusFilter, onStatusChange }) {
  return (
    <GridToolbarContainer>
      <Grid container spacing={2} alignItems="center" sx={{ p: 1 }}>
        <Grid item xs={12} md={4}>
          <TextField
            size="small"
            placeholder="Search customers, ports..."
            value={searchValue}
            onChange={(e) => onSearchChange(e.target.value)}
            sx={{ width: '100%' }}
          />
        </Grid>
        <Grid item xs={12} md={3}>
          <FormControl size="small" sx={{ width: '100%' }}>
            <InputLabel>Status Filter</InputLabel>
            <Select
              value={statusFilter}
              label="Status Filter"
              onChange={(e) => onStatusChange(e.target.value)}
            >
              <MenuItem value="">All Status</MenuItem>
              <MenuItem value="online">Online</MenuItem>
              <MenuItem value="offline">Offline</MenuItem>
              <MenuItem value="los">LOS</MenuItem>
            </Select>
          </FormControl>
        </Grid>
        <Grid item xs={12} md={5}>
          <Box display="flex" gap={1}>
            <GridToolbarFilterButton />
            <GridToolbarColumnsButton />
            <GridToolbarExport />
            <Tooltip title="Refresh Data">
              <IconButton onClick={onRefresh} size="small">
                <RefreshIcon />
              </IconButton>
            </Tooltip>
          </Box>
        </Grid>
      </Grid>
    </GridToolbarContainer>
  );
}

// Power status component
function PowerStatus({ power, type = 'rx' }) {
  if (power === null || power === undefined) {
    return <Chip label="N/A" size="small" variant="outlined" />;
  }

  let color = 'success';
  let icon = <SignalIcon />;

  if (type === 'rx') {
    if (power < -27 || power > -8) {
      color = 'error';
      icon = <WarningIcon />;
    } else if (power < -25 || power > -10) {
      color = 'warning';
      icon = <WarningIcon />;
    }
  }

  return (
    <Chip
      icon={icon}
      label={`${power} dBm`}
      size="small"
      color={color}
      variant="outlined"
    />
  );
}

// Distance status component
function DistanceStatus({ distance }) {
  if (distance === null || distance === undefined) {
    return <Chip label="N/A" size="small" variant="outlined" />;
  }

  let color = 'success';
  let icon = <SignalIcon />;

  if (distance > 25) {
    color = 'error';
    icon = <WarningIcon />;
  } else if (distance > 20) {
    color = 'warning';
    icon = <WarningIcon />;
  }

  return (
    <Chip
      icon={icon}
      label={`${distance} km`}
      size="small"
      color={color}
      variant="outlined"
    />
  );
}

// Status chip component
function StatusChip({ status }) {
  const statusConfig = {
    online: { color: 'success', icon: <SignalIcon />, label: 'Online' },
    offline: { color: 'error', icon: <NoSignalIcon />, label: 'Offline' },
    los: { color: 'warning', icon: <WarningIcon />, label: 'LOS' },
  };

  const config = statusConfig[status] || statusConfig.offline;

  return (
    <Chip
      icon={config.icon}
      label={config.label}
      size="small"
      color={config.color}
    />
  );
}

function ONTTable({ limit }) {
  const { enqueueSnackbar } = useSnackbar();
  const [searchValue, setSearchValue] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [page, setPage] = useState(0);
  const [pageSize, setPageSize] = useState(limit || 25);

  const { data, isLoading, error, refetch } = useQuery(
    ['dashboard-onts', { 
      page: page + 1, 
      limit: pageSize, 
      search: searchValue, 
      status: statusFilter 
    }],
    () => dashboardApi.getOnts({ 
      page: page + 1, 
      limit: pageSize, 
      search: searchValue, 
      status: statusFilter 
    }),
    {
      keepPreviousData: true,
      refetchInterval: 30000,
      onError: (error) => {
        console.error('ONT table error:', error);
        enqueueSnackbar('Error loading ONT data', { variant: 'error' });
      },
    }
  );

  const columns = [
    {
      field: 'customer_name',
      headerName: 'Customer',
      width: 150,
      renderCell: (params) => (
        <Box>
          <div>{params.value || 'Unknown'}</div>
          <div style={{ fontSize: '0.8em', color: '#666' }}>
            {params.row.olt_name}
          </div>
        </Box>
      ),
    },
    {
      field: 'port_info',
      headerName: 'Port/ONT',
      width: 120,
      renderCell: (params) => (
        <Box>
          <div>{params.row.port}</div>
          <div style={{ fontSize: '0.8em', color: '#666' }}>
            ONT {params.row.ont_id}
          </div>
        </Box>
      ),
    },
    {
      field: 'status',
      headerName: 'Status',
      width: 100,
      renderCell: (params) => <StatusChip status={params.value} />,
    },
    {
      field: 'rx_power',
      headerName: 'RX Power',
      width: 120,
      renderCell: (params) => <PowerStatus power={params.value} type="rx" />,
    },
    {
      field: 'tx_power',
      headerName: 'TX Power',
      width: 120,
      renderCell: (params) => <PowerStatus power={params.value} type="tx" />,
    },
    {
      field: 'distance',
      headerName: 'Distance',
      width: 120,
      renderCell: (params) => <DistanceStatus distance={params.value} />,
    },
    {
      field: 'last_seen',
      headerName: 'Last Seen',
      width: 150,
      renderCell: (params) => {
        if (!params.value) return 'Never';
        const date = new Date(params.value);
        return date.toLocaleString('id-ID');
      },
    },
    {
      field: 'actions',
      headerName: 'Actions',
      width: 80,
      sortable: false,
      renderCell: (params) => (
        <Tooltip title="Edit Customer">
          <IconButton
            size="small"
            onClick={() => {
              // TODO: Open edit dialog
              enqueueSnackbar('Edit functionality coming soon', { variant: 'info' });
            }}
          >
            <EditIcon />
          </IconButton>
        </Tooltip>
      ),
    },
  ];

  const rows = data?.data?.data || [];
  const rowCount = data?.data?.pagination?.total || 0;

  return (
    <Box sx={{ height: '100%', width: '100%' }}>
      <DataGrid
        rows={rows}
        columns={columns}
        paginationMode="server"
        rowCount={rowCount}
        page={page}
        pageSize={pageSize}
        onPageChange={setPage}
        onPageSizeChange={setPageSize}
        rowsPerPageOptions={[10, 25, 50, 100]}
        loading={isLoading}
        disableSelectionOnClick
        components={{
          Toolbar: CustomToolbar,
        }}
        componentsProps={{
          toolbar: {
            onRefresh: refetch,
            searchValue,
            onSearchChange: setSearchValue,
            statusFilter,
            onStatusChange: setStatusFilter,
          },
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
  );
}

export default ONTTable;