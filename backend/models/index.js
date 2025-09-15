/**
 * index.js - Inicialização do Sequelize e importação dos modelos
 */

const { Sequelize } = require('sequelize');
const config = require('../config/config');

const sequelize = new Sequelize({
  dialect: config.database.dialect,
  storage: config.database.storage,
  logging: config.database.logging,
});

// Importar modelos
const User = require('./User');
const Company = require('./Company');
const Point = require('./Point');
const Review = require('./Review');
const Notification = require('./Notification');
const Payment = require('./Payment');

const userModel = User.initModel(sequelize);
const companyModel = Company.initModel(sequelize);
const pointModel = Point.initModel(sequelize);
const reviewModel = Review.initModel(sequelize);
const notificationModel = Notification.initModel(sequelize);
const paymentModel = Payment.initModel(sequelize);

userModel.hasMany(pointModel, { foreignKey: 'userId' });
pointModel.belongsTo(userModel, { foreignKey: 'userId' });

companyModel.hasMany(pointModel, { foreignKey: 'companyId' });
pointModel.belongsTo(companyModel, { foreignKey: 'companyId' });

companyModel.hasMany(reviewModel, { foreignKey: 'companyId' });
reviewModel.belongsTo(companyModel, { foreignKey: 'companyId' });

userModel.hasMany(reviewModel, { foreignKey: 'userId' });
reviewModel.belongsTo(userModel, { foreignKey: 'userId' });

userModel.hasMany(notificationModel, { foreignKey: 'userId' });
notificationModel.belongsTo(userModel, { foreignKey: 'userId' });

companyModel.hasMany(notificationModel, { foreignKey: 'companyId' });
notificationModel.belongsTo(companyModel, { foreignKey: 'companyId' });

companyModel.hasMany(paymentModel, { foreignKey: 'companyId' });
paymentModel.belongsTo(companyModel, { foreignKey: 'companyId' });

module.exports = {
  sequelize,
  User: userModel,
  Company: companyModel,
  Point: pointModel,
  Review: reviewModel,
  Notification: notificationModel,
  Payment: paymentModel,
};
