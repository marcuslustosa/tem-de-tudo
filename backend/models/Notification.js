/**
 * Notification.js - Modelo Sequelize para notificações de clientes e empresas
 */

const { DataTypes, Model } = require('sequelize');

class Notification extends Model {
  static initModel(sequelize) {
    Notification.init({
      id: {
        type: DataTypes.INTEGER,
        primaryKey: true,
        autoIncrement: true,
      },
      userId: {
        type: DataTypes.INTEGER,
        allowNull: true,
      },
      companyId: {
        type: DataTypes.INTEGER,
        allowNull: true,
      },
      message: {
        type: DataTypes.STRING,
        allowNull: false,
      },
      read: {
        type: DataTypes.BOOLEAN,
        defaultValue: false,
      },
    }, {
      sequelize,
      modelName: 'Notification',
      tableName: 'notifications',
      timestamps: true,
    });
    return Notification;
  }
}

module.exports = Notification;
