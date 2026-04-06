require('dotenv').config();
const express = require('express');
const connectDB = require('./config/db');
const otherfunRoutes = require('./routes/otherfun');

const app = express();
app.use(express.json());

connectDB();

app.use('/api/otherfun', otherfunRoutes);

app.use((req, res) => {
  res.status(404).json({ success: false, message: 'Route not found' });
});

const PORT = process.env.PORT || 4000;
app.listen(PORT, () => console.log(`Server running on port ${PORT}`));
