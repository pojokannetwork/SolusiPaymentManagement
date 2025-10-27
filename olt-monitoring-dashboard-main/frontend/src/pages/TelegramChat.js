import React, { useState, useEffect } from 'react';
import {
  Container,
  Typography,
  Card,
  CardContent,
  Grid,
  Box,
  Chip,
  CircularProgress,
  TextField,
  MenuItem,
  FormControl,
  InputLabel,
  Select,
  Alert,
  Paper,
  Avatar,
  Divider,
  IconButton,
  Tooltip,
  Pagination,
} from '@mui/material';
import {
  Telegram as TelegramIcon,
  Person as PersonIcon,
  SmartToy as BotIcon,
  Refresh as RefreshIcon,
  Search as SearchIcon,
  FilterList as FilterIcon,
  AccessTime as TimeIcon,
  CheckCircle as SuccessIcon,
  Error as ErrorIcon,
} from '@mui/icons-material';
import { useQuery, useQueryClient } from 'react-query';
import { useSnackbar } from 'notistack';
import { format } from 'date-fns';
import { id as idLocale } from 'date-fns/locale';

import { useTranslation } from '../utils/translations';
import { telegramChatApi } from '../services/api';

function TelegramChat() {
  const [filters, setFilters] = useState({
    message_type: 'all',
    status: 'all',
    search: '',
    page: 1,
    limit: 10
  });
  
  const { t } = useTranslation();
  const { enqueueSnackbar } = useSnackbar();
  const queryClient = useQueryClient();

  // Load chat history
  const { data: chatHistory, isLoading, refetch } = useQuery(
    ['telegram-chat-history', filters],
    () => telegramChatApi.getAll(filters),
    {
      onError: (error) => {
        enqueueSnackbar('Error loading chat history', { variant: 'error' });
      }
    }
  );

  const handleFilterChange = (key, value) => {
    setFilters(prev => ({
      ...prev,
      [key]: value,
      page: 1 // Reset page when filtering
    }));
  };

  const handlePageChange = (event, page) => {
    setFilters(prev => ({ ...prev, page }));
  };

  const getMessageTypeIcon = (type) => {
    switch (type) {
      case 'command':
        return <BotIcon color="primary" />;
      case 'text':
        return <PersonIcon color="secondary" />;
      default:
        return <TelegramIcon />;
    }
  };

  const getStatusIcon = (status) => {
    switch (status) {
      case 'success':
        return <SuccessIcon color="success" />;
      case 'error':
        return <ErrorIcon color="error" />;
      default:
        return <TimeIcon color="action" />;
    }
  };

  const formatResponseTime = (responseTime) => {
    if (!responseTime) return '-';
    const timeDiff = new Date() - new Date(responseTime);
    return `${Math.round(timeDiff / 1000)}s ago`;
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
      <Box display="flex" alignItems="center" gap={2} mb={3}>
        <TelegramIcon color="primary" />
        <Typography variant="h4" gutterBottom>
          {t('telegramChatHistory')}
        </Typography>
        <Box sx={{ ml: 'auto' }}>
          <Tooltip title="Refresh">
            <IconButton onClick={() => refetch()}>
              <RefreshIcon />
            </IconButton>
          </Tooltip>
        </Box>
      </Box>

      {/* Filters */}
      <Card sx={{ mb: 3 }}>
        <CardContent>
          <Grid container spacing={2} alignItems="center">
            <Grid item xs={12} sm={6} md={3}>
              <TextField
                fullWidth
                variant="outlined"
                placeholder="Search messages..."
                value={filters.search}
                onChange={(e) => handleFilterChange('search', e.target.value)}
                InputProps={{
                  startAdornment: <SearchIcon color="action" sx={{ mr: 1 }} />
                }}
              />
            </Grid>
            
            <Grid item xs={12} sm={6} md={3}>
              <FormControl fullWidth>
                <InputLabel>Message Type</InputLabel>
                <Select
                  value={filters.message_type}
                  label="Message Type"
                  onChange={(e) => handleFilterChange('message_type', e.target.value)}
                >
                  <MenuItem value="all">All Types</MenuItem>
                  <MenuItem value="command">Commands</MenuItem>
                  <MenuItem value="text">Text Messages</MenuItem>
                </Select>
              </FormControl>
            </Grid>
            
            <Grid item xs={12} sm={6} md={3}>
              <FormControl fullWidth>
                <InputLabel>Status</InputLabel>
                <Select
                  value={filters.status}
                  label="Status"
                  onChange={(e) => handleFilterChange('status', e.target.value)}
                >
                  <MenuItem value="all">All Status</MenuItem>
                  <MenuItem value="success">Success</MenuItem>
                  <MenuItem value="error">Error</MenuItem>
                </Select>
              </FormControl>
            </Grid>
          </Grid>
        </CardContent>
      </Card>

      {/* Chat History List */}
      {chatHistory?.data?.chats?.length === 0 ? (
        <Alert severity="info">
          No chat history found. Chat interactions will appear here.
        </Alert>
      ) : (
        <Grid container spacing={2}>
          {chatHistory?.data?.chats?.map((chat) => (
            <Grid item xs={12} key={chat.id}>
              <Paper elevation={1} sx={{ p: 2 }}>
                <Box display="flex" alignItems="flex-start" gap={2}>
                  {/* User Avatar */}
                  <Avatar sx={{ bgcolor: 'primary.main' }}>
                    {chat.first_name?.charAt(0) || chat.username?.charAt(0) || 'U'}
                  </Avatar>

                  {/* Message Content */}
                  <Box flex={1}>
                    <Box display="flex" alignItems="center" gap={1} mb={1}>
                      <Typography variant="subtitle1" fontWeight="bold">
                        {chat.first_name && chat.last_name 
                          ? `${chat.first_name} ${chat.last_name}`
                          : chat.username || 'Unknown User'
                        }
                      </Typography>
                      <Chip 
                        size="small" 
                        label={`@${chat.username || 'unknown'}`}
                        variant="outlined"
                      />
                      <Box display="flex" alignItems="center" gap={0.5}>
                        {getMessageTypeIcon(chat.message_type)}
                        <Typography variant="caption" color="textSecondary">
                          {chat.message_type}
                        </Typography>
                      </Box>
                      <Box display="flex" alignItems="center" gap={0.5}>
                        {getStatusIcon(chat.status)}
                        <Typography variant="caption" color="textSecondary">
                          {chat.status}
                        </Typography>
                      </Box>
                      <Typography variant="caption" color="textSecondary" sx={{ ml: 'auto' }}>
                        {format(new Date(chat.created_at), 'dd/MM/yyyy HH:mm', { locale: idLocale })}
                      </Typography>
                    </Box>

                    {/* User Message */}
                    <Box sx={{ bgcolor: 'grey.100', p: 1.5, borderRadius: 1, mb: 1 }}>
                      <Typography variant="body2" color="textPrimary">
                        <strong>User:</strong> {chat.message}
                      </Typography>
                    </Box>

                    {/* Bot Response */}
                    {chat.bot_response && (
                      <Box sx={{ bgcolor: 'primary.50', p: 1.5, borderRadius: 1 }}>
                        <Typography variant="body2" color="textPrimary">
                          <strong>Bot:</strong> {chat.bot_response}
                        </Typography>
                        {chat.response_time && (
                          <Typography variant="caption" color="textSecondary">
                            Response time: {formatResponseTime(chat.response_time)}
                          </Typography>
                        )}
                      </Box>
                    )}
                  </Box>
                </Box>
              </Paper>
            </Grid>
          ))}
        </Grid>
      )}

      {/* Pagination */}
      {chatHistory?.data?.totalPages > 1 && (
        <Box display="flex" justifyContent="center" mt={3}>
          <Pagination
            count={chatHistory.data.totalPages}
            page={filters.page}
            onChange={handlePageChange}
            color="primary"
          />
        </Box>
      )}
    </Container>
  );
}

export default TelegramChat;