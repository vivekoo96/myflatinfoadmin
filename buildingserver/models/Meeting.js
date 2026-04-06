const mongoose = require('mongoose');

const meetingSchema = new mongoose.Schema(
  {
    title: { type: String, required: true, trim: true },
    description: { type: String, trim: true },
    date: { type: String },
    time: { type: String },
    dateTime: { type: Date },
    createdBy: { type: String },
  },
  { timestamps: true }
);

// Build dateTime from date + time before saving
meetingSchema.pre('save', function (next) {
  if (this.date && this.time) {
    this.dateTime = new Date(`${this.date}T${this.time}:00`);
  }
  next();
});

module.exports = mongoose.model('Meeting', meetingSchema);
