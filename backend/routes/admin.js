const express = require('express');
const router = express.Router();
const { verifyAdmin } = require('../middlewares/authMiddleware');
const adminController = require('../controllers/adminController');

// Middleware to verify admin master access
router.use(verifyAdmin);

// Admin dashboard overview
router.get('/dashboard', adminController.dashboard);

// Manage companies
router.get('/companies', adminController.listCompanies);
router.put('/companies/:id', adminController.updateCompany);
router.delete('/companies/:id', adminController.deleteCompany);

// Manage users
router.get('/users', adminController.listUsers);
router.put('/users/:id', adminController.updateUser);
router.delete('/users/:id', adminController.deleteUser);

// Manage payments
router.get('/payments', adminController.listPayments);
router.put('/payments/:id', adminController.updatePayment);

// Manage approvals
router.get('/approvals', adminController.listApprovals);
router.put('/approvals/:id', adminController.approveCompany);

// Reports
router.get('/reports', adminController.getReports);

module.exports = router;
