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
  TextField,
  Button,
  Switch,
  FormControlLabel,
  Divider,
  Alert,
  Chip,
  CircularProgress,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  IconButton,
} from '@mui/material';
import {
  Save as SaveIcon,
  NetworkCheck as TestIcon,
  Info as InfoIcon,
  Telegram as TelegramIcon,
  People as UsersIcon,
  Add as AddIcon,
  Edit as EditIcon,
  Delete as DeleteIcon,
  Language as LanguageIcon,
  Router as MikrotikIcon,
} from '@mui/icons-material';
import { useQuery, useMutation, useQueryClient } from 'react-query';
import { useSnackbar } from 'notistack';
import { useForm, Controller } from 'react-hook-form';
import { DataGrid } from '@mui/x-data-grid';

import { settingsApi, telegramUsersApi } from '../services/api';
import { useTranslation } from '../utils/translations';

function TabPanel({ children, value, index, ...other }) {
  return (
    <div
      role="tabpanel"
      hidden={value !== index}
      id={`settings-tabpanel-${index}`}
      aria-labelledby={`settings-tab-${index}`}
      {...other}
    >
      {value === index && <Box sx={{ pt: 3 }}>{children}</Box>}
    </div>
  );
}

function Settings() {
  const [tabValue, setTabValue] = useState(0);
  const [testDialogOpen, setTestDialogOpen] = useState(false);
  const [botTestResult, setBotTestResult] = useState(null);
  const { enqueueSnackbar } = useSnackbar();
  const queryClient = useQueryClient();
  const { t, setLanguage, language } = useTranslation();

  const { control, handleSubmit, reset, watch, setValue } = useForm({
    defaultValues: {
      telegram_bot_token: '',
      telegram_chat_id: '',
      notification_enabled: true,
      mikrotik_enabled: false,
      mikrotik_host: '',
      mikrotik_username: '',
      mikrotik_password: '',
      mikrotik_port: '8728',
      language: 'id',
      system_name: 'OLT Monitoring System',
      polling_interval: 30000,
      rx_power_safe_min: -25,
      rx_power_safe_max: -8,
      rx_power_warning_min: -27,
      rx_power_warning_max: -25,
      distance_safe_max: 20,
      distance_warning_max: 25,
    }
  });

  // Load settings
  const { data: settings, isLoading } = useQuery(
    'settings',
    settingsApi.getAll,
    {
      onSuccess: (data) => {
        if (data?.data) {
          const { settings: settingsData, thresholds } = data.data;
          
          // Set form values
          Object.keys(settingsData).forEach(key => {
            setValue(key, settingsData[key].value);
          });

          if (thresholds.rx_power) {
            setValue('rx_power_safe_min', thresholds.rx_power.safe_min);
            setValue('rx_power_safe_max', thresholds.rx_power.safe_max);
            setValue('rx_power_warning_min', thresholds.rx_power.warning_min);
            setValue('rx_power_warning_max', thresholds.rx_power.warning_max);
          }

          if (thresholds.distance) {
            setValue('distance_safe_max', thresholds.distance.safe_max);
            setValue('distance_warning_max', thresholds.distance.warning_max);
          }
        }
      },
      onError: (error) => {
        enqueueSnackbar('Error loading settings', { variant: 'error' });
      }
    }
  );

  // Save settings mutation
  const saveSettingsMutation = useMutation(
    (data) => {
      const payload = {
        settings: {
          telegram_bot_token: data.telegram_bot_token,
          telegram_chat_id: data.telegram_chat_id,
          notification_enabled: data.notification_enabled.toString(),
          mikrotik_enabled: data.mikrotik_enabled.toString(),
          mikrotik_host: data.mikrotik_host,
          mikrotik_username: data.mikrotik_username,
          mikrotik_password: data.mikrotik_password,
          mikrotik_port: data.mikrotik_port,
          language: data.language,
          system_name: data.system_name,
          polling_interval: data.polling_interval.toString(),
        },
        thresholds: {
          rx_power: {
            safe_min: parseFloat(data.rx_power_safe_min),
            safe_max: parseFloat(data.rx_power_safe_max),
            warning_min: parseFloat(data.rx_power_warning_min),
            warning_max: parseFloat(data.rx_power_warning_max),
            danger_min: -999,
            danger_max: parseFloat(data.rx_power_warning_min),
          },
          distance: {
            safe_min: 0,
            safe_max: parseFloat(data.distance_safe_max),
            warning_min: parseFloat(data.distance_safe_max),
            warning_max: parseFloat(data.distance_warning_max),
            danger_min: parseFloat(data.distance_warning_max),
            danger_max: 999,
          },
        },
      };
      return settingsApi.update(payload);
    },
    {
      onSuccess: (result, variables) => {
        enqueueSnackbar(t('settings.saved'), { variant: 'success' });
        queryClient.invalidateQueries('settings');
        
        // Update language if changed
        if (variables.language && variables.language !== language) {
          setLanguage(variables.language);
        }
      },
      onError: (error) => {
        enqueueSnackbar(t('settings.errorSaving'), { variant: 'error' });
      },
    }
  );

  // Test bot mutation
  const testBotMutation = useMutation(
    (data) => settingsApi.testTelegram(data),
    {
      onSuccess: (result) => {
        setBotTestResult(result.data);
        setTestDialogOpen(true);
      },
      onError: (error) => {
        setBotTestResult({ success: false, error: error.message });
        setTestDialogOpen(true);
      },
    }
  );

  const onSubmit = (data) => {
    saveSettingsMutation.mutate(data);
  };

  const handleTestBot = () => {
    const token = watch('telegram_bot_token');
    const chatId = watch('telegram_chat_id');
    
    if (!token) {
      enqueueSnackbar('Please enter bot token first', { variant: 'warning' });
      return;
    }

    testBotMutation.mutate({ token, chat_id: chatId });
  };

  if (isLoading) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" minHeight="50vh">
        <CircularProgress />
      </Box>
    );
  }

  return (
    <Container maxWidth="lg">
      <Typography variant="h4" gutterBottom>
        {t('navigation.settings')}
      </Typography>

      <Card>
        <CardContent>
          <Tabs value={tabValue} onChange={(e, v) => setTabValue(v)} aria-label="settings tabs">
            <Tab label={t('settings.telegramBot')} icon={<TelegramIcon />} />
            <Tab label={t('settings.users')} icon={<UsersIcon />} />
            <Tab label={t('settings.thresholds')} icon={<InfoIcon />} />
            <Tab label={t('settings.system')} icon={<LanguageIcon />} />
          </Tabs>

          <form onSubmit={handleSubmit(onSubmit)}>
            {/* Telegram Settings */}
            <TabPanel value={tabValue} index={0}>
              <Grid container spacing={3}>
                <Grid item xs={12}>
                  <Alert severity="info" sx={{ mb: 2 }}>
                    <Typography variant="body2">
                      {t('settings.telegramInstructions')}
                    </Typography>
                  </Alert>
                </Grid>

                <Grid item xs={12} md={8}>
                  <Controller
                    name="telegram_bot_token"
                    control={control}
                    render={({ field }) => (
                      <TextField
                        {...field}
                        fullWidth
                        label="Bot Token"
                        placeholder="123456789:ABCdefGHIjklMNOpqrSTUvwxyz123456789"
                        helperText="Token dari @BotFather"
                      />
                    )}
                  />
                </Grid>

                <Grid item xs={12} md={4}>
                  <Box display="flex" gap={1} height="100%" alignItems="end">
                    <Button
                      variant="outlined"
                      onClick={handleTestBot}
                      disabled={testBotMutation.isLoading}
                      startIcon={testBotMutation.isLoading ? <CircularProgress size={16} /> : <TestIcon />}
                    >
                      Test Bot
                    </Button>
                  </Box>
                </Grid>

                <Grid item xs={12} md={6}>
                  <Controller
                    name="telegram_chat_id"
                    control={control}
                    render={({ field }) => (
                      <TextField
                        {...field}
                        fullWidth
                        label="Default Chat ID"
                        placeholder="-1001234567890"
                        helperText="Chat ID untuk notifikasi (opsional)"
                      />
                    )}
                  />
                </Grid>

                <Grid item xs={12} md={6}>
                  <Controller
                    name="telegram_whitelist_enabled"
                    control={control}
                    render={({ field }) => (
                      <FormControlLabel
                        control={<Switch {...field} checked={field.value} />}
                        label="Enable User Whitelist"
                      />
                    )}
                  />
                  <Typography variant="caption" display="block" color="textSecondary">
                    Batasi akses bot hanya untuk pengguna yang terdaftar
                  </Typography>
                </Grid>

                <Grid item xs={12} md={6}>
                  <Controller
                    name="notification_enabled"
                    control={control}
                    render={({ field }) => (
                      <FormControlLabel
                        control={<Switch {...field} checked={field.value} />}
                        label="Enable Telegram Notifications"
                      />
                    )}
                  />
                </Grid>

                {/* Mikrotik Integration Section */}
                <Grid item xs={12}>
                  <Divider sx={{ my: 2 }} />
                  <Box display="flex" alignItems="center" gap={1} mb={2}>
                    <MikrotikIcon />
                    <Typography variant="h6">
                      {t('settings.mikrotikIntegration')}
                    </Typography>
                  </Box>
                </Grid>

                <Grid item xs={12} md={6}>
                  <Controller
                    name="mikrotik_enabled"
                    control={control}
                    render={({ field }) => (
                      <FormControlLabel
                        control={<Switch {...field} checked={field.value} />}
                        label={t('settings.enableMikrotik')}
                      />
                    )}
                  />
                  <Typography variant="caption" display="block" color="textSecondary">
                    {t('settings.mikrotikDescription')}
                  </Typography>
                </Grid>

                {watch('mikrotik_enabled') && (
                  <>
                    <Grid item xs={12} md={6}>
                      <Controller
                        name="mikrotik_host"
                        control={control}
                        render={({ field }) => (
                          <TextField
                            {...field}
                            fullWidth
                            label={t('settings.mikrotikHost')}
                            placeholder="192.168.1.1"
                            helperText={t('settings.mikrotikHostHelper')}
                          />
                        )}
                      />
                    </Grid>

                    <Grid item xs={12} md={3}>
                      <Controller
                        name="mikrotik_port"
                        control={control}
                        render={({ field }) => (
                          <TextField
                            {...field}
                            fullWidth
                            label={t('settings.mikrotikPort')}
                            type="number"
                            helperText="Default: 8728"
                          />
                        )}
                      />
                    </Grid>

                    <Grid item xs={12} md={6}>
                      <Controller
                        name="mikrotik_username"
                        control={control}
                        render={({ field }) => (
                          <TextField
                            {...field}
                            fullWidth
                            label={t('settings.mikrotikUsername')}
                            helperText={t('settings.mikrotikUsernameHelper')}
                          />
                        )}
                      />
                    </Grid>

                    <Grid item xs={12} md={6}>
                      <Controller
                        name="mikrotik_password"
                        control={control}
                        render={({ field }) => (
                          <TextField
                            {...field}
                            fullWidth
                            label={t('settings.mikrotikPassword')}
                            type="password"
                            helperText={t('settings.mikrotikPasswordHelper')}
                          />
                        )}
                      />
                    </Grid>
                  </>
                )}
              </Grid>
            </TabPanel>

            {/* Telegram Users Management */}
            <TabPanel value={tabValue} index={1}>
              <TelegramUsersManagement />
            </TabPanel>

            {/* Threshold Settings */}
            <TabPanel value={tabValue} index={2}>
              <Grid container spacing={3}>
                <Grid item xs={12}>
                  <Typography variant="h6" gutterBottom>
                    RX Power Thresholds (dBm)
                  </Typography>
                  <Divider sx={{ mb: 2 }} />
                </Grid>

                <Grid item xs={12} md={3}>
                  <Controller
                    name="rx_power_safe_max"
                    control={control}
                    render={({ field }) => (
                      <TextField
                        {...field}
                        fullWidth
                        label="Safe Max"
                        type="number"
                        inputProps={{ step: 0.1 }}
                        helperText="🟢 Safe upper limit"
                      />
                    )}
                  />
                </Grid>

                <Grid item xs={12} md={3}>
                  <Controller
                    name="rx_power_safe_min"
                    control={control}
                    render={({ field }) => (
                      <TextField
                        {...field}
                        fullWidth
                        label="Safe Min"
                        type="number"
                        inputProps={{ step: 0.1 }}
                        helperText="🟢 Safe lower limit"
                      />
                    )}
                  />
                </Grid>

                <Grid item xs={12} md={3}>
                  <Controller
                    name="rx_power_warning_max"
                    control={control}
                    render={({ field }) => (
                      <TextField
                        {...field}
                        fullWidth
                        label="Warning Max"
                        type="number"
                        inputProps={{ step: 0.1 }}
                        helperText="🟡 Warning upper limit"
                      />
                    )}
                  />
                </Grid>

                <Grid item xs={12} md={3}>
                  <Controller
                    name="rx_power_warning_min"
                    control={control}
                    render={({ field }) => (
                      <TextField
                        {...field}
                        fullWidth
                        label="Warning Min"
                        type="number"
                        inputProps={{ step: 0.1 }}
                        helperText="🟡 Warning lower limit"
                      />
                    )}
                  />
                </Grid>

                <Grid item xs={12}>
                  <Typography variant="h6" gutterBottom sx={{ mt: 2 }}>
                    Distance Thresholds (km)
                  </Typography>
                  <Divider sx={{ mb: 2 }} />
                </Grid>

                <Grid item xs={12} md={6}>
                  <Controller
                    name="distance_safe_max"
                    control={control}
                    render={({ field }) => (
                      <TextField
                        {...field}
                        fullWidth
                        label="Safe Distance"
                        type="number"
                        inputProps={{ step: 0.1 }}
                        helperText="🟢 Safe distance limit"
                      />
                    )}
                  />
                </Grid>

                <Grid item xs={12} md={6}>
                  <Controller
                    name="distance_warning_max"
                    control={control}
                    render={({ field }) => (
                      <TextField
                        {...field}
                        fullWidth
                        label="Warning Distance"
                        type="number"
                        inputProps={{ step: 0.1 }}
                        helperText="🟡 Warning distance limit"
                      />
                    )}
                  />
                </Grid>
              </Grid>
            </TabPanel>

            {/* System Settings */}
            <TabPanel value={tabValue} index={3}>
              <Grid container spacing={3}>
                <Grid item xs={12} md={6}>
                  <Controller
                    name="language"
                    control={control}
                    render={({ field }) => (
                      <FormControl fullWidth>
                        <InputLabel>{t('settings.language')}</InputLabel>
                        <Select {...field} label={t('settings.language')}>
                          <MenuItem value="en">English</MenuItem>
                          <MenuItem value="id">Bahasa Indonesia</MenuItem>
                        </Select>
                      </FormControl>
                    )}
                  />
                </Grid>

                <Grid item xs={12} md={6}>
                  <Controller
                    name="system_name"
                    control={control}
                    render={({ field }) => (
                      <TextField
                        {...field}
                        fullWidth
                        label={t('settings.systemName')}
                        helperText={t('settings.systemNameHelper')}
                      />
                    )}
                  />
                </Grid>

                <Grid item xs={12} md={6}>
                  <Controller
                    name="polling_interval"
                    control={control}
                    render={({ field }) => (
                      <TextField
                        {...field}
                        fullWidth
                        label={t('settings.pollingInterval')}
                        type="number"
                        inputProps={{ min: 10000, step: 1000 }}
                        helperText={t('settings.pollingIntervalHelper')}
                      />
                    )}
                  />
                </Grid>
              </Grid>
            </TabPanel>

            <Divider sx={{ my: 3 }} />

            <Box display="flex" justifyContent="flex-end">
              <Button
                type="submit"
                variant="contained"
                startIcon={saveSettingsMutation.isLoading ? <CircularProgress size={16} /> : <SaveIcon />}
                disabled={saveSettingsMutation.isLoading}
              >
                {t('common.save')}
              </Button>
            </Box>
          </form>
        </CardContent>
      </Card>

      {/* Test Result Dialog */}
      <Dialog open={testDialogOpen} onClose={() => setTestDialogOpen(false)}>
        <DialogTitle>Bot Test Result</DialogTitle>
        <DialogContent>
          {botTestResult && (
            <Box>
              {botTestResult.success ? (
                <Alert severity="success" sx={{ mb: 2 }}>
                  Bot test successful!
                </Alert>
              ) : (
                <Alert severity="error" sx={{ mb: 2 }}>
                  Bot test failed: {botTestResult.error}
                </Alert>
              )}
              
              {botTestResult.bot_info && (
                <Box>
                  <Typography variant="body2" gutterBottom>
                    Bot Information:
                  </Typography>
                  <Chip label={`@${botTestResult.bot_info.username}`} sx={{ mr: 1 }} />
                  <Chip label={botTestResult.bot_info.first_name} />
                </Box>
              )}
            </Box>
          )}
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setTestDialogOpen(false)}>Close</Button>
        </DialogActions>
      </Dialog>
    </Container>
  );
}

// Telegram Users Management Component
function TelegramUsersManagement() {
  const { enqueueSnackbar } = useSnackbar();
  const queryClient = useQueryClient();
  const [dialogOpen, setDialogOpen] = useState(false);
  const [selectedUser, setSelectedUser] = useState(null);
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);

  // Fetch telegram users
  const { data: users = [], isLoading, error } = useQuery('telegram-users', telegramUsersApi.getAll);

  // Add user mutation
  const addMutation = useMutation(telegramUsersApi.add, {
    onSuccess: () => {
      enqueueSnackbar('User berhasil ditambahkan', { variant: 'success' });
      queryClient.invalidateQueries('telegram-users');
      setDialogOpen(false);
    },
    onError: (error) => {
      enqueueSnackbar(
        `Error menambahkan user: ${error.response?.data?.error || error.message}`,
        { variant: 'error' }
      );
    },
  });

  // Update user mutation
  const updateMutation = useMutation(
    ({ id, ...data }) => telegramUsersApi.update(id, data),
    {
      onSuccess: () => {
        enqueueSnackbar('User berhasil diupdate', { variant: 'success' });
        queryClient.invalidateQueries('telegram-users');
        setDialogOpen(false);
      },
      onError: (error) => {
        enqueueSnackbar(
          `Error update user: ${error.response?.data?.error || error.message}`,
          { variant: 'error' }
        );
      },
    }
  );

  // Delete user mutation
  const deleteMutation = useMutation(telegramUsersApi.delete, {
    onSuccess: () => {
      enqueueSnackbar('User berhasil dihapus', { variant: 'success' });
      queryClient.invalidateQueries('telegram-users');
      setDeleteDialogOpen(false);
    },
    onError: (error) => {
      enqueueSnackbar(
        `Error menghapus user: ${error.response?.data?.error || error.message}`,
        { variant: 'error' }
      );
    },
  });

  const handleAdd = () => {
    setSelectedUser(null);
    setDialogOpen(true);
  };

  const handleEdit = (user) => {
    setSelectedUser(user);
    setDialogOpen(true);
  };

  const handleDelete = (user) => {
    setSelectedUser(user);
    setDeleteDialogOpen(true);
  };

  const confirmDelete = () => {
    if (selectedUser) {
      deleteMutation.mutate(selectedUser.id);
    }
  };

  const columns = [
    {
      field: 'chat_id',
      headerName: 'Chat ID',
      width: 150,
    },
    {
      field: 'username',
      headerName: 'Username',
      width: 150,
      renderCell: (params) => params.value ? `@${params.value}` : '-',
    },
    {
      field: 'first_name',
      headerName: 'Nama',
      width: 150,
      renderCell: (params) => {
        const fullName = [params.row.first_name, params.row.last_name].filter(Boolean).join(' ');
        return fullName || '-';
      },
    },
    {
      field: 'chat_type',
      headerName: 'Type',
      width: 100,
      renderCell: (params) => (
        <Chip
          label={params.value || 'private'}
          size="small"
          color={params.value === 'private' ? 'primary' : 'secondary'}
        />
      ),
    },
    {
      field: 'is_active',
      headerName: 'Status',
      width: 100,
      renderCell: (params) => (
        <Chip
          label={params.value ? 'Active' : 'Inactive'}
          size="small"
          color={params.value ? 'success' : 'error'}
        />
      ),
    },
    {
      field: 'created_at',
      headerName: 'Ditambahkan',
      width: 150,
      renderCell: (params) => {
        if (!params.value) return '-';
        return new Date(params.value).toLocaleDateString('id-ID');
      },
    },
    {
      field: 'actions',
      headerName: 'Actions',
      width: 150,
      renderCell: (params) => (
        <Box>
          <IconButton size="small" onClick={() => handleEdit(params.row)}>
            <EditIcon />
          </IconButton>
          <IconButton size="small" color="error" onClick={() => handleDelete(params.row)}>
            <DeleteIcon />
          </IconButton>
        </Box>
      ),
    },
  ];

  if (error) {
    return (
      <Alert severity="error">
        Error loading telegram users: {error.message}
      </Alert>
    );
  }

  return (
    <Box>
      <Box display="flex" justifyContent="space-between" alignItems="center" mb={3}>
        <Typography variant="h6">
          Manajemen Pengguna Telegram
        </Typography>
        <Button
          variant="contained"
          startIcon={<AddIcon />}
          onClick={handleAdd}
        >
          Tambah User
        </Button>
      </Box>

      <Alert severity="info" sx={{ mb: 2 }}>
        Daftarkan pengguna atau grup yang diizinkan menggunakan bot Telegram. 
        Fitur whitelist dapat diaktifkan/nonaktifkan melalui settings sistem.
      </Alert>

      <Box sx={{ height: 400, width: '100%' }}>
        <DataGrid
          rows={users}
          columns={columns}
          loading={isLoading}
          disableSelectionOnClick
          hideFooterSelectedRowCount
        />
      </Box>

      {/* Add/Edit User Dialog */}
      <TelegramUserDialog
        open={dialogOpen}
        onClose={() => setDialogOpen(false)}
        user={selectedUser}
        onSubmit={(data) => {
          if (selectedUser) {
            updateMutation.mutate({ id: selectedUser.id, ...data });
          } else {
            addMutation.mutate(data);
          }
        }}
        isLoading={addMutation.isLoading || updateMutation.isLoading}
      />

      {/* Delete Confirmation Dialog */}
      <Dialog open={deleteDialogOpen} onClose={() => setDeleteDialogOpen(false)}>
        <DialogTitle>Konfirmasi Hapus</DialogTitle>
        <DialogContent>
          <Typography>
            Apakah Anda yakin ingin menghapus user "{selectedUser?.username || selectedUser?.chat_id}"?
          </Typography>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setDeleteDialogOpen(false)}>Batal</Button>
          <Button 
            onClick={confirmDelete} 
            color="error" 
            disabled={deleteMutation.isLoading}
          >
            {deleteMutation.isLoading ? 'Menghapus...' : 'Hapus'}
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
}

// Dialog for Add/Edit Telegram User
function TelegramUserDialog({ open, onClose, user = null, onSubmit, isLoading }) {
  const { control, handleSubmit, reset } = useForm({
    defaultValues: {
      chat_id: user?.chat_id || '',
      username: user?.username || '',
      first_name: user?.first_name || '',
      last_name: user?.last_name || '',
      chat_type: user?.chat_type || 'private',
      is_active: user?.is_active !== undefined ? user.is_active : true,
    }
  });

  React.useEffect(() => {
    if (open) {
      reset({
        chat_id: user?.chat_id || '',
        username: user?.username || '',
        first_name: user?.first_name || '',
        last_name: user?.last_name || '',
        chat_type: user?.chat_type || 'private',
        is_active: user?.is_active !== undefined ? user.is_active : true,
      });
    }
  }, [open, user, reset]);

  const handleClose = () => {
    reset();
    onClose();
  };

  return (
    <Dialog open={open} onClose={handleClose} maxWidth="sm" fullWidth>
      <form onSubmit={handleSubmit(onSubmit)}>
        <DialogTitle>
          {user ? 'Edit User Telegram' : 'Tambah User Telegram'}
        </DialogTitle>
        <DialogContent>
          <Grid container spacing={2} sx={{ mt: 1 }}>
            <Grid item xs={12}>
              <Controller
                name="chat_id"
                control={control}
                rules={{ required: 'Chat ID is required' }}
                render={({ field, fieldState: { error } }) => (
                  <TextField
                    {...field}
                    fullWidth
                    label="Chat ID"
                    placeholder="Contoh: 123456789 atau -1001234567890"
                    error={!!error}
                    helperText={error?.message || "ID chat user atau grup Telegram"}
                  />
                )}
              />
            </Grid>
            <Grid item xs={12} md={6}>
              <Controller
                name="username"
                control={control}
                render={({ field }) => (
                  <TextField
                    {...field}
                    fullWidth
                    label="Username"
                    placeholder="Tanpa @"
                    helperText="Username Telegram (opsional)"
                  />
                )}
              />
            </Grid>
            <Grid item xs={12} md={6}>
              <Controller
                name="chat_type"
                control={control}
                render={({ field }) => (
                  <FormControl fullWidth>
                    <InputLabel>Type</InputLabel>
                    <Select {...field} label="Type">
                      <MenuItem value="private">Private</MenuItem>
                      <MenuItem value="group">Group</MenuItem>
                      <MenuItem value="supergroup">Supergroup</MenuItem>
                      <MenuItem value="channel">Channel</MenuItem>
                    </Select>
                  </FormControl>
                )}
              />
            </Grid>
            <Grid item xs={12} md={6}>
              <Controller
                name="first_name"
                control={control}
                render={({ field }) => (
                  <TextField
                    {...field}
                    fullWidth
                    label="Nama Depan"
                    helperText="Nama depan user (opsional)"
                  />
                )}
              />
            </Grid>
            <Grid item xs={12} md={6}>
              <Controller
                name="last_name"
                control={control}
                render={({ field }) => (
                  <TextField
                    {...field}
                    fullWidth
                    label="Nama Belakang"
                    helperText="Nama belakang user (opsional)"
                  />
                )}
              />
            </Grid>
            <Grid item xs={12}>
              <Controller
                name="is_active"
                control={control}
                render={({ field }) => (
                  <FormControlLabel
                    control={
                      <Switch
                        checked={field.value}
                        onChange={(e) => field.onChange(e.target.checked)}
                      />
                    }
                    label="User Aktif"
                  />
                )}
              />
            </Grid>
          </Grid>
        </DialogContent>
        <DialogActions>
          <Button onClick={handleClose}>Batal</Button>
          <Button 
            type="submit" 
            variant="contained"
            disabled={isLoading}
          >
            {isLoading ? 'Menyimpan...' : (user ? 'Update' : 'Tambah')}
          </Button>
        </DialogActions>
      </form>
    </Dialog>
  );
}

export default Settings;