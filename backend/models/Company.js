/**
 * Company.js - Modelo Sequelize para empresas
 * Segue padrões PSR-12 adaptados para JavaScript, código limpo e seguro.
 */

const { DataTypes, Model } = require('sequelize');

class Company extends Model {
  static initModel(sequelize) {
    Company.init({
      id: {
        type: DataTypes.INTEGER,
        primaryKey: true,
        autoIncrement: true,
      },
      name: {
        type: DataTypes.STRING(150),
        allowNull: false,
      },
      email: {
        type: DataTypes.STRING(150),
        allowNull: false,
        unique: true,
        validate: {
          isEmail: true,
        },
      },
      phone: {
        type: DataTypes.STRING(20),
        allowNull: true,
      },
      address: {
        type: DataTypes.STRING(255),
        allowNull: true,
      },
      approved: {
        type: DataTypes.BOOLEAN,
        defaultValue: false,
      },
      paymentConfirmed: {
        type: DataTypes.BOOLEAN,
        defaultValue: false,
      },
      createdAt: {
        type: DataTypes.DATE,
        defaultValue: DataTypes.NOW,
      },
      updatedAt: {
        type: DataTypes.DATE,
        defaultValue: DataTypes.NOW,
      },
      masterProfileId: {
        type: DataTypes.INTEGER,
        allowNull: true,
      },
    }, {
      sequelize,
      modelName: 'Company',
      tableName: 'companies',
    });
    return Company;
  }
}

module.exports = Company;
