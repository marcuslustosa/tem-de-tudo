/**
 * Payment.js - Modelo Sequelize para pagamentos de empresas anunciantes
 */

const { DataTypes, Model } = require('sequelize');

class Payment extends Model {
  static initModel(sequelize) {
    Payment.init({
      id: {
        type: DataTypes.INTEGER,
        primaryKey: true,
        autoIncrement: true,
      },
      companyId: {
        type: DataTypes.INTEGER,
        allowNull: false,
      },
      amount: {
        type: DataTypes.FLOAT,
        allowNull: false,
      },
      status: {
        type: DataTypes.ENUM('pending', 'confirmed', 'cancelled'),
        defaultValue: 'pending',
      },
      paymentMethod: {
        type: DataTypes.STRING,
        allowNull: true,
      },
      transactionId: {
        type: DataTypes.STRING,
        allowNull: true,
      },
    }, {
      sequelize,
      modelName: 'Payment',
      tableName: 'payments',
      timestamps: true,
    });
    return Payment;
  }
}

module.exports = Payment;
