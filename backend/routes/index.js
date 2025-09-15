/**
 * index.js - Agregador das rotas da API
 */

const express = require('express');
const router = express.Router();

const authRoutes = require('./auth');
const clientRoutes = require('./clients');
const companyRoutes = require('./companies');
const paymentRoutes = require('./payments');
const pointRoutes = require('./points');
const reviewRoutes = require('./reviews');
const notificationRoutes = require('./notifications');
const tempPaymentsRoutes = require('./tempPayments');
const qrCodesRoutes = require('./qrCodes');
const adminRoutes = require('./admin');

router.use('/auth', authRoutes);
router.use('/clients', clientRoutes);
router.use('/companies', companyRoutes);
router.use('/payments', paymentRoutes);
router.use('/points', pointRoutes);
router.use('/reviews', reviewRoutes);
router.use('/notifications', notificationRoutes);
router.use('/tempPayments', tempPaymentsRoutes);
router.use('/qrCodes', qrCodesRoutes);
router.use('/admin', adminRoutes);

module.exports = router;
