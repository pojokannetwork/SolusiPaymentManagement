const readline = require('readline');
const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');

const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout
});

function question(prompt) {
  return new Promise((resolve) => {
    rl.question(prompt, resolve);
  });
}

async function setupDatabase() {
  console.log('🔧 OLT Monitoring Database Setup');
  console.log('=====================================\n');

  try {
    // Get MySQL credentials
    const host = await question('MySQL Host (default: localhost): ') || 'localhost';
    const port = await question('MySQL Port (default: 3306): ') || '3306';
    const user = await question('MySQL Username (default: root): ') || 'root';
    const password = await question('MySQL Password: ');

    console.log('\n⏳ Testing connection...');

    // Test connection
    const connection = await mysql.createConnection({
      host,
      port: parseInt(port),
      user,
      password
    });

    console.log('✅ Connected to MySQL successfully!');

    // Create database
    console.log('📊 Creating database...');
    await connection.execute('CREATE DATABASE IF NOT EXISTS olt_monitoring');
    console.log('✅ Database "olt_monitoring" created!');

    await connection.end();

    // Update .env file
    console.log('📝 Updating .env file...');
    const envPath = path.join(__dirname, '.env');
    let envContent = '';

    if (fs.existsSync(envPath)) {
      envContent = fs.readFileSync(envPath, 'utf8');
    }

    // Update or add database config
    const dbConfig = `# Database Configuration
DB_HOST=${host}
DB_PORT=${port}
DB_NAME=olt_monitoring
DB_USER=${user}
DB_PASSWORD=${password}`;

    // Replace existing config or append
    if (envContent.includes('DB_HOST=')) {
      envContent = envContent.replace(
        /# Database Configuration[\s\S]*?(?=\n#|\n[A-Z]|$)/,
        dbConfig
      );
    } else {
      envContent = dbConfig + '\n\n' + envContent;
    }

    fs.writeFileSync(envPath, envContent);
    console.log('✅ .env file updated!');

    // Run migrations
    console.log('🔄 Running database migrations...');
    const { runMigrations } = require('./database/migrate');
    await runMigrations();
    console.log('✅ Database setup completed!');

    console.log('\n🎉 Setup successful!');
    console.log('\nNext steps:');
    console.log('1. Run: npm run dev (start backend)');
    console.log('2. Run: cd ../frontend && npm start (start frontend)');
    console.log('3. Open: http://localhost:3000');

  } catch (error) {
    console.error('\n❌ Setup failed:', error.message);
    console.log('\nPlease check:');
    console.log('- MySQL service is running');
    console.log('- Credentials are correct');
    console.log('- User has database creation privileges');
    process.exit(1);
  } finally {
    rl.close();
  }
}

setupDatabase();