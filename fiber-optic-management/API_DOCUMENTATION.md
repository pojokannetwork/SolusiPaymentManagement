# API Documentation - Fiber Optic Management System

## Base URL
```
http://your-domain.com/api
```

## Authentication

Aplikasi menggunakan JWT (JSON Web Token) untuk autentikasi. Token harus disertakan dalam header Authorization untuk semua endpoint yang memerlukan autentikasi.

```
Authorization: Bearer <your-jwt-token>
```

## Endpoints

### Authentication

#### POST /auth/login
Login pengguna dan mendapatkan JWT token.

**Request Body:**
```json
{
  "username": "admin",
  "password": "admin123"
}
```

**Response:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "user": {
    "id": 1,
    "username": "admin",
    "role": "admin"
  }
}
```

#### POST /auth/verify
Verifikasi JWT token.

**Headers:**
```
Authorization: Bearer <token>
```

**Response:**
```json
{
  "user": {
    "id": 1,
    "username": "admin",
    "role": "admin"
  }
}
```

### Joint Closures

#### GET /joint-closures
Mendapatkan semua joint closures.

**Headers:**
```
Authorization: Bearer <token>
```

**Response:**
```json
[
  {
    "id": 1,
    "name": "JC-001",
    "address": "Jl. Sudirman No. 123",
    "latitude": -6.200000,
    "longitude": 106.816666,
    "photo_path": "/api/uploads/photo.jpg",
    "core_connections_count": 12,
    "created_at": "2025-01-01T10:00:00Z"
  }
]
```

#### POST /joint-closures
Membuat joint closure baru.

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "JC-002",
  "address": "Jl. Thamrin No. 456",
  "latitude": -6.195000,
  "longitude": 106.822000,
  "photo_path": "/api/uploads/photo2.jpg"
}
```

**Response:**
```json
{
  "id": 2,
  "name": "JC-002",
  "address": "Jl. Thamrin No. 456",
  "latitude": -6.195000,
  "longitude": 106.822000,
  "photo_path": "/api/uploads/photo2.jpg",
  "core_connections_count": 0,
  "created_at": "2025-01-01T11:00:00Z"
}
```

#### GET /joint-closures/{id}
Mendapatkan detail joint closure beserta core connections.

**Headers:**
```
Authorization: Bearer <token>
```

**Response:**
```json
{
  "id": 1,
  "name": "JC-001",
  "address": "Jl. Sudirman No. 123",
  "latitude": -6.200000,
  "longitude": 106.816666,
  "photo_path": "/api/uploads/photo.jpg",
  "core_connections_count": 2,
  "created_at": "2025-01-01T10:00:00Z",
  "core_connections": [
    {
      "id": 1,
      "source_tube_color": "Biru",
      "source_core_color": "Putih",
      "dest_tube_color": "Hijau",
      "dest_core_color": "Merah",
      "network_name": "Network A",
      "attenuation_before": 0.5,
      "attenuation_after": 0.3
    }
  ]
}
```

#### PUT /joint-closures/{id}
Update joint closure.

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "JC-001-Updated",
  "address": "Jl. Sudirman No. 123 (Updated)",
  "latitude": -6.200001,
  "longitude": 106.816667
}
```

#### DELETE /joint-closures/{id}
Hapus joint closure dan semua core connections terkait.

**Headers:**
```
Authorization: Bearer <token>
```

**Response:** 204 No Content

### Core Connections

#### GET /core-connections
Mendapatkan semua core connections atau filter berdasarkan closure_id.

**Headers:**
```
Authorization: Bearer <token>
```

**Query Parameters:**
- `closure_id` (optional): Filter berdasarkan joint closure ID

**Response:**
```json
[
  {
    "id": 1,
    "closure_id": 1,
    "source_tube_color": "Biru",
    "source_core_color": "Putih",
    "dest_tube_color": "Hijau",
    "dest_core_color": "Merah",
    "network_name": "Network A",
    "attenuation_before": 0.5,
    "attenuation_after": 0.3,
    "created_at": "2025-01-01T10:30:00Z"
  }
]
```

#### POST /core-connections
Membuat core connection baru.

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "closure_id": 1,
  "source_tube_color": "Kuning",
  "source_core_color": "Biru",
  "dest_tube_color": "Orange",
  "dest_core_color": "Hijau",
  "network_name": "Network B",
  "attenuation_before": 0.4,
  "attenuation_after": 0.2
}
```

#### POST /core-connections/bulk
Membuat multiple core connections sekaligus.

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "connections": [
    {
      "closure_id": 1,
      "source_tube_color": "Merah",
      "source_core_color": "Putih",
      "dest_tube_color": "Biru",
      "dest_core_color": "Kuning",
      "network_name": "Network C",
      "attenuation_before": 0.6,
      "attenuation_after": 0.4
    },
    {
      "closure_id": 1,
      "source_tube_color": "Hijau",
      "source_core_color": "Orange",
      "dest_tube_color": "Ungu",
      "dest_core_color": "Pink",
      "network_name": "Network D",
      "attenuation_before": 0.3,
      "attenuation_after": 0.1
    }
  ]
}
```

#### PUT /core-connections/{id}
Update core connection.

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "source_tube_color": "Biru Updated",
  "source_core_color": "Putih Updated",
  "network_name": "Network A Updated",
  "attenuation_after": 0.25
}
```

#### DELETE /core-connections/{id}
Hapus core connection.

**Headers:**
```
Authorization: Bearer <token>
```

**Response:** 204 No Content

### File Upload

#### POST /upload/photo
Upload foto untuk joint closure.

**Headers:**
```
Authorization: Bearer <token>
Content-Type: multipart/form-data
```

**Request Body:**
```
file: <image-file>
```

**Supported formats:** PNG, JPG, JPEG, GIF, WebP
**Max size:** 5MB

**Response:**
```json
{
  "filename": "20250101_120000_uuid.jpg",
  "original_filename": "closure_photo.jpg",
  "file_path": "/api/uploads/20250101_120000_uuid.jpg",
  "file_size": 1024000,
  "upload_time": "2025-01-01T12:00:00Z"
}
```

#### GET /uploads/{filename}
Mengakses file yang diupload.

**Response:** File binary

#### DELETE /upload/delete/{filename}
Hapus file yang diupload.

**Headers:**
```
Authorization: Bearer <token>
```

**Response:**
```json
{
  "message": "File deleted successfully"
}
```

### Health Check

#### GET /health
Cek status aplikasi.

**Response:**
```json
{
  "status": "OK",
  "message": "Fiber Optic Management API is running"
}
```

## Error Responses

### 400 Bad Request
```json
{
  "error": "Validation error message"
}
```

### 401 Unauthorized
```json
{
  "error": "Token is missing"
}
```

### 403 Forbidden
```json
{
  "error": "Admin access required"
}
```

### 404 Not Found
```json
{
  "error": "Resource not found"
}
```

### 500 Internal Server Error
```json
{
  "error": "Internal server error"
}
```

## Rate Limiting

API menggunakan rate limiting untuk mencegah abuse:
- **Login**: 5 requests per minute per IP
- **Upload**: 10 requests per minute per user
- **General API**: 100 requests per minute per user

## Data Models

### Joint Closure
```json
{
  "id": "integer",
  "name": "string (required, unique)",
  "address": "string (optional)",
  "latitude": "float (optional)",
  "longitude": "float (optional)",
  "photo_path": "string (optional)",
  "core_connections_count": "integer (computed)",
  "created_at": "datetime"
}
```

### Core Connection
```json
{
  "id": "integer",
  "closure_id": "integer (required, foreign key)",
  "source_tube_color": "string (required)",
  "source_core_color": "string (required)",
  "dest_tube_color": "string (required)",
  "dest_core_color": "string (required)",
  "network_name": "string (optional)",
  "attenuation_before": "float (optional)",
  "attenuation_after": "float (optional)",
  "created_at": "datetime"
}
```

### User
```json
{
  "id": "integer",
  "username": "string (required, unique)",
  "role": "string (admin/user)",
  "created_at": "datetime"
}
```

## Example Usage

### JavaScript/Fetch
```javascript
// Login
const loginResponse = await fetch('/api/auth/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    username: 'admin',
    password: 'admin123'
  })
});

const { token } = await loginResponse.json();

// Get joint closures
const closuresResponse = await fetch('/api/joint-closures', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});

const closures = await closuresResponse.json();
```

### Python/Requests
```python
import requests

# Login
login_response = requests.post('http://localhost:5003/api/auth/login', json={
    'username': 'admin',
    'password': 'admin123'
})

token = login_response.json()['token']

# Get joint closures
headers = {'Authorization': f'Bearer {token}'}
closures_response = requests.get('http://localhost:5003/api/joint-closures', headers=headers)
closures = closures_response.json()
```

### cURL
```bash
# Login
curl -X POST http://localhost:5003/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'

# Get joint closures
curl -X GET http://localhost:5003/api/joint-closures \
  -H "Authorization: Bearer <your-token>"
```

