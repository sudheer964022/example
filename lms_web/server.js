const express = require('express');
const bodyParser = require('body-parser');
const nodemailer = require('nodemailer');
const crypto = require('crypto');
const bcrypt = require('bcrypt');

const app = express();
app.use(bodyParser.json());

// Mock database (Replace with your actual database)
const users = {
  "user@example.com": { password: "hashed_password", resetToken: null, resetTokenExpiration: null },
};

// Nodemailer Transporter
const transporter = nodemailer.createTransport({
  service: 'gmail',
  auth: {
    user: 'sr9346335294@gmail.com', // Your email
    pass: 'Lenovo@1686218', // Your email password or app password
  },
});

// 1. Request Password Reset
app.post('/request-password-reset', (req, res) => {
  const { email } = req.body;

  if (!users[email]) {
    return res.status(404).json({ message: "User not found" });
  }

  // Generate reset token and expiration time
  const resetToken = crypto.randomBytes(32).toString('hex');
  const resetTokenExpiration = Date.now() + 3600000; // 1 hour

  users[email].resetToken = resetToken;
  users[email].resetTokenExpiration = resetTokenExpiration;

  // Send reset email
  const resetLink = `http://localhost:3000/reset-password/${resetToken}`;
  const mailOptions = {
    to: email,
    subject: 'Password Reset Request',
    html: `<p>You requested a password reset. Click <a href="${resetLink}">here</a> to reset your password. This link is valid for 1 hour.</p>`,
  };

  transporter.sendMail(mailOptions, (err, info) => {
    if (err) {
      console.error(err);
      return res.status(500).json({ message: 'Failed to send email' });
    }
    res.status(200).json({ message: 'Password reset email sent' });
  });
});

// 2. Reset Password
app.post('/reset-password/:token', async (req, res) => {
  const { token } = req.params;
  const { password } = req.body;

  // Find user by token
  const user = Object.values(users).find(
    (user) => user.resetToken === token && user.resetTokenExpiration > Date.now()
  );

  if (!user) {
    return res.status(400).json({ message: 'Invalid or expired token' });
  }

  // Hash and save the new password
  const hashedPassword = await bcrypt.hash(password, 10);
  user.password = hashedPassword;

  // Clear reset token
  user.resetToken = null;
  user.resetTokenExpiration = null;

  res.status(200).json({ message: 'Password reset successfully' });
});

// Start Server
app.listen(3000, () => console.log('Server running on http://localhost:3000'));
