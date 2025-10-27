import React, { useState } from 'react';
import {
  Container,
  Typography,
  Card,
  CardContent,
  Grid,
  Box,
  Button,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  TextField,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  IconButton,
  Tooltip,
  Chip,
  Alert,
  Divider,
} from '@mui/material';
import {
  Add as AddIcon,
  Edit as EditIcon,
  Delete as DeleteIcon,
  PlayArrow as TestIcon,
  Refresh as RefreshIcon,
  NetworkCheck as TestConnectionIcon,
} from '@mui/icons-material';
import { DataGrid, GridToolbarContainer, GridToolbarExport } from '@mui/x-data-grid';
import { useQuery, useMutation, useQueryClient } from 'react-query';
import { useSnackbar } from 'notistack';
import { useForm, Controller } from 'react-hook-form';

import { oltApi } from '../services/api';

function CustomToolbar() {
  return (
    <GridToolbarContainer>
      <GridToolbarExport />
    </GridToolbarContainer>
  );
}

function OLTFormDialog({ open, onClose, olt = null, onSuccess }) {
  const { enqueueSnackbar } = useSnackbar();
  const queryClient = useQueryClient();

  const { control, handleSubmit, reset, formState: { errors }, watch } = useForm({
    defaultValues: {
      name: olt?.name || '',
      location: olt?.location || '',
      ip_address: olt?.ip_address || '',
      port: olt?.port || 22,
      username: olt?.username || '',
      password: olt?.password || '',
      olt_type: olt?.type || 'hioso',
      connection_method: olt?.connection_method || 'ssh',
      snmp_enabled: olt?.snmp_enabled || false,
      snmp_port: olt?.snmp_port || 161,
      snmp_community: olt?.snmp_community || 'public',
      snmp_version: olt?.snmp_version || 'v2c',
      snmpv3_read_user: olt?.snmpv3_read_user || '',
      snmpv3_write_user: olt?.snmpv3_write_user || '',
      snmpv3_trap_user: olt?.snmpv3_trap_user || '',
      snmp_auth_protocol: olt?.snmp_auth_protocol || 'MD5',
      snmp_auth_password: olt?.snmp_auth_password || '',
      snmp_priv_protocol: olt?.snmp_priv_protocol || 'DES',
      snmp_priv_password: olt?.snmp_priv_password || '',
    }
  });

  const watchConnectionMethod = watch('connection_method');

  const mutation = useMutation(
    (data) => {
      if (olt) {
        return oltApi.update(olt.id, data);
      } else {
        return oltApi.create(data);
      }
    },
    {
      onSuccess: () => {
        enqueueSnackbar(
          olt ? 'OLT updated successfully' : 'OLT created successfully',
          { variant: 'success' }
        );
        queryClient.invalidateQueries('olts');
        onSuccess();
        onClose();
      },
      onError: (error) => {
        enqueueSnackbar(
          `Error ${olt ? 'updating' : 'creating'} OLT: ${error.response?.data?.error || error.message}`,
          { variant: 'error' }
        );
      },
    }
  );

  const testConnectionMutation = useMutation(
    (data) => oltApi.testConnection(data),
    {
      onSuccess: (response) => {
        enqueueSnackbar(
          'Connection test successful! OLT is reachable.',
          { variant: 'success' }
        );
      },
      onError: (error) => {
        enqueueSnackbar(
          `Connection test failed: ${error.response?.data?.error || error.message}`,
          { variant: 'error' }
        );
      },
    }
  );

  const onSubmit = (data) => {
    mutation.mutate(data);
  };

  const handleTestConnection = () => {
    const formData = control._formValues;
    testConnectionMutation.mutate({
      ip_address: formData.ip_address,
      port: formData.port,
      username: formData.username,
      password: formData.password,
      olt_type: formData.olt_type,
      connection_method: formData.connection_method,
      snmp_enabled: formData.snmp_enabled,
      snmp_port: formData.snmp_port,
      snmp_community: formData.snmp_community,
      snmp_version: formData.snmp_version
    });
  };

  const handleClose = () => {
    reset();
    onClose();
  };

  return (
    <Dialog open={open} onClose={handleClose} maxWidth="md" fullWidth>
      <DialogTitle>
        {olt ? 'Edit OLT' : 'Add New OLT'}
      </DialogTitle>
      <form onSubmit={handleSubmit(onSubmit)}>
        <DialogContent>
          <Grid container spacing={2}>
            <Grid item xs={12} md={6}>
              <Controller
                name="name"
                control={control}
                rules={{ required: 'Name is required' }}
                render={({ field }) => (
                  <TextField
                    {...field}
                    fullWidth
                    label="OLT Name"
                    error={!!errors.name}
                    helperText={errors.name?.message}
                    margin="normal"
                  />
                )}
              />
            </Grid>
            <Grid item xs={12} md={6}>
              <Controller
                name="location"
                control={control}
                render={({ field }) => (
                  <TextField
                    {...field}
                    fullWidth
                    label="Location"
                    margin="normal"
                  />
                )}
              />
            </Grid>
            <Grid item xs={12} md={6}>
              <Controller
                name="ip_address"
                control={control}
                rules={{ 
                  required: 'IP Address is required',
                  pattern: {
                    value: /^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/,
                    message: 'Invalid IP address format'
                  }
                }}
                render={({ field }) => (
                  <TextField
                    {...field}
                    fullWidth
                    label="IP Address"
                    error={!!errors.ip_address}
                    helperText={errors.ip_address?.message}
                    margin="normal"
                  />
                )}
              />
            </Grid>
            <Grid item xs={12} md={6}>
              <Controller
                name="port"
                control={control}
                rules={{ required: 'Port is required' }}
                render={({ field }) => (
                  <TextField
                    {...field}
                    fullWidth
                    label="SSH Port"
                    type="number"
                    inputProps={{ min: 1, max: 65535 }}
                    error={!!errors.port}
                    helperText={errors.port?.message}
                    margin="normal"
                  />
                )}
              />
            </Grid>
            <Grid item xs={12} md={4}>
              <Controller
                name="olt_type"
                control={control}
                render={({ field }) => (
                  <FormControl fullWidth margin="normal">
                    <InputLabel>OLT Type</InputLabel>
                    <Select {...field} label="OLT Type">
                      <MenuItem value="hioso">Hioso</MenuItem>
                      <MenuItem value="zte">ZTE</MenuItem>
                      <MenuItem value="huawei">Huawei</MenuItem>
                      <MenuItem value="other">Other</MenuItem>
                    </Select>
                  </FormControl>
                )}
              />
            </Grid>
            
            <Grid item xs={12}>
              <Typography variant="h6" gutterBottom sx={{ mt: 2 }}>
                Connection Settings
              </Typography>
              <Divider sx={{ mb: 2 }} />
            </Grid>
            
            <Grid item xs={12} md={4}>
              <Controller
                name="connection_method"
                control={control}
                render={({ field }) => (
                  <FormControl fullWidth margin="normal">
                    <InputLabel>Connection Method</InputLabel>
                    <Select {...field} label="Connection Method">
                      <MenuItem value="ssh">SSH</MenuItem>
                      <MenuItem value="snmp">SNMP</MenuItem>
                    </Select>
                  </FormControl>
                )}
              />
            </Grid>

            {/* SSH Configuration */}
            {watchConnectionMethod === 'ssh' && (
              <>
                <Grid item xs={12} md={4}>
                  <Controller
                    name="username"
                    control={control}
                    rules={{ required: watchConnectionMethod === 'ssh' ? 'Username is required for SSH' : false }}
                    render={({ field }) => (
                      <TextField
                        {...field}
                        fullWidth
                        label="SSH Username"
                        error={!!errors.username}
                        helperText={errors.username?.message}
                        margin="normal"
                      />
                    )}
                  />
                </Grid>
                <Grid item xs={12} md={4}>
                  <Controller
                    name="password"
                    control={control}
                    rules={{ required: watchConnectionMethod === 'ssh' ? 'Password is required for SSH' : false }}
                    render={({ field }) => (
                      <TextField
                        {...field}
                        fullWidth
                        label="SSH Password"
                        type="password"
                        error={!!errors.password}
                        helperText={errors.password?.message}
                        margin="normal"
                      />
                    )}
                  />
                </Grid>
              </>
            )}

            {/* SNMP Configuration */}
            {watchConnectionMethod === 'snmp' && (
              <>
                <Grid item xs={12}>
                  <Typography variant="h6" sx={{ mt: 2 }}>
                    SNMP Configuration
                  </Typography>
                  <Divider sx={{ mb: 2 }} />
                </Grid>
                
                <Grid item xs={12} md={4}>
                  <Controller
                    name="snmp_version"
                    control={control}
                    render={({ field }) => (
                      <FormControl fullWidth margin="normal">
                        <InputLabel>SNMP Version</InputLabel>
                        <Select {...field} label="SNMP Version">
                          <MenuItem value="v1">SNMPv1</MenuItem>
                          <MenuItem value="v2c">SNMPv2c</MenuItem>
                          <MenuItem value="v3">SNMPv3</MenuItem>
                        </Select>
                      </FormControl>
                    )}
                  />
                </Grid>

                <Grid item xs={12} md={4}>
                  <Controller
                    name="snmp_port"
                    control={control}
                    render={({ field }) => (
                      <TextField
                        {...field}
                        fullWidth
                        label="SNMP Port"
                        type="number"
                        inputProps={{ min: 1, max: 65535 }}
                        helperText="Default: 161"
                        margin="normal"
                      />
                    )}
                  />
                </Grid>

                {/* SNMPv1/v2c Community */}
                {(watch('snmp_version') === 'v1' || watch('snmp_version') === 'v2c') && (
                  <Grid item xs={12} md={4}>
                    <Controller
                      name="snmp_community"
                      control={control}
                      rules={{ required: 'SNMP Community is required for v1/v2c' }}
                      render={({ field }) => (
                        <TextField
                          {...field}
                          fullWidth
                          label="SNMP Community"
                          error={!!errors.snmp_community}
                          helperText={errors.snmp_community?.message || "Default: public"}
                          margin="normal"
                        />
                      )}
                    />
                  </Grid>
                )}

                {/* SNMPv3 Configuration */}
                {watch('snmp_version') === 'v3' && (
                  <>
                    <Grid item xs={12}>
                      <Typography variant="h6" sx={{ mt: 2 }}>
                        SNMPv3 Users Configuration
                      </Typography>
                      <Divider sx={{ mb: 2 }} />
                    </Grid>

                    <Grid item xs={12} md={4}>
                      <Controller
                        name="snmpv3_read_user"
                        control={control}
                        render={({ field }) => (
                          <TextField
                            {...field}
                            fullWidth
                            label="SNMPv3 Read User"
                            helperText="Username for read operations"
                            margin="normal"
                          />
                        )}
                      />
                    </Grid>

                    <Grid item xs={12} md={4}>
                      <Controller
                        name="snmpv3_write_user"
                        control={control}
                        render={({ field }) => (
                          <TextField
                            {...field}
                            fullWidth
                            label="SNMPv3 Write User"
                            helperText="Username for write operations"
                            margin="normal"
                          />
                        )}
                      />
                    </Grid>

                    <Grid item xs={12} md={4}>
                      <Controller
                        name="snmpv3_trap_user"
                        control={control}
                        render={({ field }) => (
                          <TextField
                            {...field}
                            fullWidth
                            label="SNMPv3 Trap User"
                            helperText="Username for trap operations"
                            margin="normal"
                          />
                        )}
                      />
                    </Grid>

                    <Grid item xs={12}>
                      <Typography variant="h6" sx={{ mt: 2 }}>
                        SNMPv3 Authentication & Privacy
                      </Typography>
                      <Divider sx={{ mb: 2 }} />
                    </Grid>

                    <Grid item xs={12} md={3}>
                      <Controller
                        name="snmp_auth_protocol"
                        control={control}
                        render={({ field }) => (
                          <FormControl fullWidth margin="normal">
                            <InputLabel>Auth Protocol</InputLabel>
                            <Select {...field} label="Auth Protocol">
                              <MenuItem value="MD5">MD5</MenuItem>
                              <MenuItem value="SHA">SHA</MenuItem>
                              <MenuItem value="SHA224">SHA-224</MenuItem>
                              <MenuItem value="SHA256">SHA-256</MenuItem>
                              <MenuItem value="SHA384">SHA-384</MenuItem>
                              <MenuItem value="SHA512">SHA-512</MenuItem>
                            </Select>
                          </FormControl>
                        )}
                      />
                    </Grid>

                    <Grid item xs={12} md={3}>
                      <Controller
                        name="snmp_auth_password"
                        control={control}
                        render={({ field }) => (
                          <TextField
                            {...field}
                            fullWidth
                            label="Auth Password"
                            type="password"
                            helperText="Min 8 characters"
                            margin="normal"
                          />
                        )}
                      />
                    </Grid>

                    <Grid item xs={12} md={3}>
                      <Controller
                        name="snmp_priv_protocol"
                        control={control}
                        render={({ field }) => (
                          <FormControl fullWidth margin="normal">
                            <InputLabel>Privacy Protocol</InputLabel>
                            <Select {...field} label="Privacy Protocol">
                              <MenuItem value="DES">DES</MenuItem>
                              <MenuItem value="3DES">3DES</MenuItem>
                              <MenuItem value="AES">AES</MenuItem>
                              <MenuItem value="AES192">AES-192</MenuItem>
                              <MenuItem value="AES256">AES-256</MenuItem>
                            </Select>
                          </FormControl>
                        )}
                      />
                    </Grid>

                    <Grid item xs={12} md={3}>
                      <Controller
                        name="snmp_priv_password"
                        control={control}
                        render={({ field }) => (
                          <TextField
                            {...field}
                            fullWidth
                            label="Privacy Password"
                            type="password"
                            helperText="Min 8 characters"
                            margin="normal"
                          />
                        )}
                      />
                    </Grid>
                  </>
                )}
              </>
            )}

            {/* Legacy fields for backward compatibility */}
            <Grid item xs={12} md={6} style={{ display: 'none' }}>
              <Controller
                name="username"
                control={control}
                render={({ field }) => (
                  <TextField
                    {...field}
                    fullWidth
                    label="Username"
                    margin="normal"
                  />
                )}
              />
            </Grid>
            <Grid item xs={12} md={6}>
              <Controller
                name="password"
                control={control}
                rules={{ required: 'Password is required' }}
                render={({ field }) => (
                  <TextField
                    {...field}
                    fullWidth
                    label="Password"
                    type="password"
                    error={!!errors.password}
                    helperText={errors.password?.message}
                    margin="normal"
                  />
                )}
              />
            </Grid>
          </Grid>
        </DialogContent>
        <DialogActions>
          <Button onClick={handleClose}>Cancel</Button>
          <Button 
            onClick={handleTestConnection}
            variant="outlined"
            startIcon={<TestConnectionIcon />}
            disabled={testConnectionMutation.isLoading}
          >
            {testConnectionMutation.isLoading ? 'Testing...' : 'Test Connection'}
          </Button>
          <Button 
            type="submit" 
            variant="contained"
            disabled={mutation.isLoading}
          >
            {mutation.isLoading ? 'Saving...' : (olt ? 'Update' : 'Create')}
          </Button>
        </DialogActions>
      </form>
    </Dialog>
  );
}

function OLTManagement() {
  const [dialogOpen, setDialogOpen] = useState(false);
  const [selectedOlt, setSelectedOlt] = useState(null);
  const { enqueueSnackbar } = useSnackbar();
  const queryClient = useQueryClient();

  const { data: olts, isLoading, refetch } = useQuery('olts', oltApi.getAll, {
    onError: (error) => {
      enqueueSnackbar('Error loading OLT data', { variant: 'error' });
    },
  });

  const deleteMutation = useMutation(oltApi.delete, {
    onSuccess: () => {
      enqueueSnackbar('OLT deleted successfully', { variant: 'success' });
      queryClient.invalidateQueries('olts');
    },
    onError: (error) => {
      enqueueSnackbar('Error deleting OLT', { variant: 'error' });
    },
  });

  const testMutation = useMutation(oltApi.testConnection, {
    onSuccess: (result) => {
      if (result.data.success) {
        enqueueSnackbar('Connection test successful', { variant: 'success' });
      } else {
        enqueueSnackbar(`Connection test failed: ${result.data.error}`, { variant: 'error' });
      }
    },
    onError: (error) => {
      enqueueSnackbar('Connection test failed', { variant: 'error' });
    },
  });

  const handleAdd = () => {
    setSelectedOlt(null);
    setDialogOpen(true);
  };

  const handleEdit = (olt) => {
    setSelectedOlt(olt);
    setDialogOpen(true);
  };

  const handleDelete = (olt) => {
    if (window.confirm(`Are you sure you want to delete OLT "${olt.name}"?`)) {
      deleteMutation.mutate(olt.id);
    }
  };

  const handleTest = (olt) => {
    testMutation.mutate(olt.id);
  };

  const columns = [
    {
      field: 'name',
      headerName: 'Name',
      width: 150,
      renderCell: (params) => (
        <Box>
          <div>{params.value}</div>
          <div style={{ fontSize: '0.8em', color: '#666' }}>
            {params.row.location || 'No location'}
          </div>
        </Box>
      ),
    },
    {
      field: 'ip_address',
      headerName: 'IP Address',
      width: 130,
    },
    {
      field: 'port',
      headerName: 'Port',
      width: 80,
      type: 'number',
    },
    {
      field: 'type',
      headerName: 'Type',
      width: 100,
      renderCell: (params) => (
        <Chip
          label={(params.value || 'unknown').toUpperCase()}
          size="small"
          variant="outlined"
          color="primary"
        />
      ),
    },
    {
      field: 'connection_method',
      headerName: 'Connection',
      width: 100,
      renderCell: (params) => (
        <Chip
          label={(params.value || 'ssh').toUpperCase()}
          size="small"
          variant="outlined"
          color={params.value === 'snmp' ? 'secondary' : 'info'}
        />
      ),
    },
    {
      field: 'status',
      headerName: 'Status',
      width: 100,
      renderCell: (params) => (
        <Chip
          label={(params.value || 'offline').toUpperCase()}
          size="small"
          color={params.value === 'online' ? 'success' : 'error'}
        />
      ),
    },
    {
      field: 'stats',
      headerName: 'ONTs',
      width: 120,
      renderCell: (params) => (
        <Box>
          <div style={{ color: '#4caf50' }}>
            Online: {params.row.online_onts || 0}
          </div>
          <div style={{ color: '#f44336' }}>
            Offline: {params.row.offline_onts || 0}
          </div>
        </Box>
      ),
    },
    {
      field: 'total_ports',
      headerName: 'Ports',
      width: 80,
    },
    {
      field: 'updated_at',
      headerName: 'Last Update',
      width: 150,
      renderCell: (params) => {
        if (!params.value) return 'Never';
        return new Date(params.value).toLocaleString('id-ID');
      },
    },
    {
      field: 'actions',
      headerName: 'Actions',
      width: 180,
      sortable: false,
      renderCell: (params) => (
        <Box display="flex" gap={1}>
          <Tooltip title="Test Connection">
            <IconButton
              size="small"
              onClick={() => handleTest(params.row)}
              disabled={testMutation.isLoading}
            >
              <TestIcon />
            </IconButton>
          </Tooltip>
          <Tooltip title="Edit OLT">
            <IconButton
              size="small"
              onClick={() => handleEdit(params.row)}
            >
              <EditIcon />
            </IconButton>
          </Tooltip>
          <Tooltip title="Delete OLT">
            <IconButton
              size="small"
              onClick={() => handleDelete(params.row)}
              disabled={deleteMutation.isLoading}
              color="error"
            >
              <DeleteIcon />
            </IconButton>
          </Tooltip>
        </Box>
      ),
    },
  ];

  const rows = olts?.data || [];

  return (
    <Container maxWidth="xl">
      <Box display="flex" justifyContent="space-between" alignItems="center" mb={3}>
        <Typography variant="h4">
          OLT Management
        </Typography>
        <Box display="flex" gap={1}>
          <Button
            variant="outlined"
            startIcon={<RefreshIcon />}
            onClick={refetch}
          >
            Refresh
          </Button>
          <Button
            variant="contained"
            startIcon={<AddIcon />}
            onClick={handleAdd}
          >
            Add OLT
          </Button>
        </Box>
      </Box>

      {rows.length === 0 && !isLoading && (
        <Alert severity="info" sx={{ mb: 2 }}>
          No OLTs configured. Click "Add OLT" to get started.
        </Alert>
      )}

      <Card>
        <CardContent sx={{ p: 0 }}>
          <Box sx={{ height: 600, width: '100%' }}>
            <DataGrid
              rows={rows}
              columns={columns}
              pageSize={10}
              rowsPerPageOptions={[10, 25, 50]}
              loading={isLoading}
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
        </CardContent>
      </Card>

      <OLTFormDialog
        open={dialogOpen}
        onClose={() => setDialogOpen(false)}
        olt={selectedOlt}
        onSuccess={() => refetch()}
      />
    </Container>
  );
}

export default OLTManagement;