import React from 'react';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  Legend,
} from 'recharts';
import {
  Typography,
  Box,
  CircularProgress,
} from '@mui/material';

// Custom tooltip for power chart
function CustomTooltip({ active, payload, label }) {
  if (active && payload && payload.length) {
    return (
      <Box
        sx={{
          bgcolor: 'background.paper',
          p: 1,
          border: '1px solid',
          borderColor: 'divider',
          borderRadius: 1,
          boxShadow: 2,
        }}
      >
        <Typography variant="body2" fontWeight="bold">
          {label}
        </Typography>
        {payload.map((entry, index) => (
          <Typography
            key={index}
            variant="body2"
            sx={{ color: entry.color }}
          >
            {entry.name}: {entry.value?.toFixed(2)} dBm
          </Typography>
        ))}
      </Box>
    );
  }
  return null;
}

function PowerChart({ data = [] }) {
  if (!data || data.length === 0) {
    return (
      <Box
        display="flex"
        flexDirection="column"
        justifyContent="center"
        alignItems="center"
        height="100%"
      >
        <Typography variant="body2" color="textSecondary">
          No power data available
        </Typography>
      </Box>
    );
  }

  // Transform data for chart
  const chartData = data.map((item) => ({
    name: item.olt_name || 'Unknown',
    avgRxPower: item.avg_rx_power || 0,
    minRxPower: item.min_rx_power || 0,
    maxRxPower: item.max_rx_power || 0,
    ontCount: item.ont_count || 0,
  }));

  return (
    <Box height="100%" width="100%">
      <ResponsiveContainer width="100%" height="100%">
        <BarChart
          data={chartData}
          margin={{
            top: 5,
            right: 30,
            left: 20,
            bottom: 5,
          }}
        >
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis 
            dataKey="name" 
            fontSize={12}
            angle={-45}
            textAnchor="end"
            height={60}
          />
          <YAxis 
            fontSize={12}
            label={{ value: 'Power (dBm)', angle: -90, position: 'insideLeft' }}
          />
          <Tooltip content={<CustomTooltip />} />
          <Legend />
          <Bar 
            dataKey="avgRxPower" 
            fill="#1976d2" 
            name="Avg RX Power"
            radius={[2, 2, 0, 0]}
          />
          <Bar 
            dataKey="minRxPower" 
            fill="#ff9800" 
            name="Min RX Power"
            radius={[2, 2, 0, 0]}
          />
          <Bar 
            dataKey="maxRxPower" 
            fill="#4caf50" 
            name="Max RX Power"
            radius={[2, 2, 0, 0]}
          />
        </BarChart>
      </ResponsiveContainer>
    </Box>
  );
}

export default PowerChart;