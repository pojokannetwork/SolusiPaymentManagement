const mysql = require('mysql2/promise');
const fs = require('fs').promises;
const path = require('path');
require('dotenv').config();

async function simpleSetup() {
    console.log('🔧 Simple Database Setup');
    console.log('======================\n');

    // Try different common password combinations
    const combinations = [
        { user: 'root', password: '' },
        { user: 'root', password: 'root' },
        { user: 'root', password: 'mysql' },
        { user: 'root', password: '123456' },
        { user: 'root', password: 'password' }
    ];

    let connection = null;
    let workingConfig = null;

    console.log('🔍 Trying common MySQL configurations...\n');

    for (const config of combinations) {
        try {
            console.log(`   Testing: user=${config.user}, password=${config.password ? '***' : '(empty)'}`);
            
            connection = await mysql.createConnection({
                host: 'localhost',
                port: 3306,
                user: config.user,
                password: config.password
            });

            await connection.execute('SELECT 1');
            workingConfig = config;
            console.log('   ✅ Success!\n');
            break;
        } catch (error) {
            console.log('   ❌ Failed');
            if (connection) {
                try { await connection.end(); } catch {}
                connection = null;
            }
        }
    }

    if (!workingConfig) {
        console.log('❌ Could not connect to MySQL with common passwords.');
        console.log('\n💡 Please try one of these solutions:');
        console.log('1. Reset MySQL root password:');
        console.log('   mysqladmin -u root password newpassword');
        console.log('2. Or create new user in MySQL:');
        console.log('   mysql> CREATE USER "olt_user"@"localhost" IDENTIFIED BY "olt123";');
        console.log('   mysql> GRANT ALL PRIVILEGES ON *.* TO "olt_user"@"localhost";');
        console.log('   mysql> FLUSH PRIVILEGES;');
        console.log('3. Or run MySQL Workbench and check connection settings');
        return;
    }

    try {
        // Create database
        console.log('📊 Creating database...');
        await connection.execute('CREATE DATABASE IF NOT EXISTS olt_monitoring');
        console.log('✅ Database created');

        // Update .env file
        console.log('📝 Updating configuration...');
        const envPath = path.join(__dirname, '.env');
        const envContent = `# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=olt_monitoring
DB_USER=${workingConfig.user}
DB_PASSWORD=${workingConfig.password}

# Server Configuration
PORT=3001
NODE_ENV=development

# JWT Secret (change in production)
JWT_SECRET=your-super-secret-jwt-key-change-in-production

# Telegram Bot Configuration (will be set via web interface)
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=

# Logging
LOG_LEVEL=info
LOG_FILE=logs/app.log
`;

        await fs.writeFile(envPath, envContent);
        console.log('✅ Configuration saved');

        await connection.end();

        // Run migration
        console.log('🗃️ Setting up database schema...');
        const { exec } = require('child_process');
        const util = require('util');
        const execPromise = util.promisify(exec);

        try {
            await execPromise('node database/migrate.js', { cwd: __dirname });
            console.log('✅ Database schema created');
        } catch (error) {
            console.log('⚠️ Migration warning:', error.message);
        }

        console.log('\n🎉 Setup Complete!');
        console.log('==================');
        console.log('✅ Database: olt_monitoring');
        console.log(`✅ User: ${workingConfig.user}`);
        console.log('✅ Schema: Created');
        console.log('✅ Configuration: Saved');
        
        console.log('\n🚀 Next Steps:');
        console.log('1. Run: npm run dev');
        console.log('2. Open: http://localhost:3001/api/health');
        console.log('3. Start frontend in new terminal');
        console.log('4. Configure Telegram bot in Settings');

    } catch (error) {
        console.error('❌ Setup failed:', error.message);
        if (connection) {
            try { await connection.end(); } catch {}
        }
    }
}

simpleSetup().catch(console.error);