const mysql = require('mysql2/promise');
const fs = require('fs').promises;
const path = require('path');
require('dotenv').config();

async function checkStatus() {
    console.log('🔍 Checking OLT Monitoring System Status...\n');

    // Check Node.js version
    console.log('📋 System Requirements:');
    console.log(`   Node.js: ${process.version} ${process.version >= 'v16.0.0' ? '✅' : '❌ (Need v16+)'}`);
    
    // Check environment file
    const envPath = path.join(__dirname, '.env');
    try {
        const envContent = await fs.readFile(envPath, 'utf8');
        console.log('   .env file: ✅ Found');
        
        const hasDbPassword = envContent.includes('DB_PASSWORD=') && !envContent.includes('DB_PASSWORD=your_mysql_password');
        console.log(`   Database config: ${hasDbPassword ? '✅' : '❌ Need to configure MySQL password'}`);
    } catch (error) {
        console.log('   .env file: ❌ Not found');
    }

    // Check node_modules
    try {
        await fs.access(path.join(__dirname, 'node_modules'));
        console.log('   Dependencies: ✅ Installed');
    } catch {
        console.log('   Dependencies: ❌ Run npm install');
    }

    // Test database connection
    console.log('\n📊 Database Connection:');
    try {
        const connection = await mysql.createConnection({
            host: process.env.DB_HOST || 'localhost',
            port: process.env.DB_PORT || 3306,
            user: process.env.DB_USER || 'root',
            password: process.env.DB_PASSWORD || '',
            database: process.env.DB_NAME || 'olt_monitoring'
        });

        await connection.execute('SELECT 1');
        console.log('   MySQL Connection: ✅ Success');
        
        // Check tables
        const [tables] = await connection.execute('SHOW TABLES');
        if (tables.length > 0) {
            console.log('   Database Schema: ✅ Migrated');
            console.log(`   Tables: ${tables.map(t => t[`Tables_in_${process.env.DB_NAME || 'olt_monitoring'}`]).join(', ')}`);
        } else {
            console.log('   Database Schema: ❌ Run npm run migrate');
        }
        
        await connection.end();
    } catch (error) {
        console.log('   MySQL Connection: ❌ Failed');
        console.log(`   Error: ${error.message}`);
        
        if (error.code === 'ER_ACCESS_DENIED_ERROR') {
            console.log('   💡 Solution: Run npm run setup to configure database');
        }
    }

    // Check ports
    console.log('\n🌐 Port Status:');
    const net = require('net');
    
    const checkPort = (port) => {
        return new Promise((resolve) => {
            const server = net.createServer();
            server.listen(port, () => {
                server.once('close', () => resolve(false));
                server.close();
            });
            server.on('error', () => resolve(true));
        });
    };

    const port3001InUse = await checkPort(3001);
    const port3000InUse = await checkPort(3000);
    
    console.log(`   Port 3001 (Backend): ${port3001InUse ? '🟡 In use' : '✅ Available'}`);
    console.log(`   Port 3000 (Frontend): ${port3000InUse ? '🟡 In use' : '✅ Available'}`);

    // Check logs directory
    console.log('\n📁 Files Status:');
    try {
        await fs.access(path.join(__dirname, 'logs'));
        console.log('   Logs directory: ✅ Present');
    } catch {
        console.log('   Logs directory: 📝 Will be created automatically');
    }

    console.log('\n🎯 Next Steps:');
    console.log('   1. Fix any ❌ issues above');
    console.log('   2. Run: npm run dev (to start backend)');
    console.log('   3. In new terminal: cd ../frontend && npm start');
    console.log('   4. Open: http://localhost:3000');
}

checkStatus().catch(console.error);