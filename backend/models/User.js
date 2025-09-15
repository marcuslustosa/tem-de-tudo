/**
 * User.js - Modelo Sequelize para usuários (clientes)
 * Segue padrões PSR-12 adaptados para JavaScript, código limpo e seguro.
 */

const { DataTypes, Model } = require('sequelize');
const bcrypt = require('bcrypt');

class User extends Model {
  static initModel(sequelize) {
    User.init({
      id: {
        type: DataTypes.INTEGER,
        primaryKey: true,
        autoIncrement: true,
      },
      name: {
        type: DataTypes.STRING(100),
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
      passwordHash: {
        type: DataTypes.STRING,
        allowNull: false,
      },
      points: {
        type: DataTypes.INTEGER,
        defaultValue: 0,
      },
      createdAt: {
        type: DataTypes.DATE,
        defaultValue: DataTypes.NOW,
      },
      updatedAt: {
        type: DataTypes.DATE,
        defaultValue: DataTypes.NOW,
      },
    }, {
      sequelize,
      modelName: 'User',
      tableName: 'users',
      hooks: {
        beforeCreate: async (user) => {
          if (user.passwordHash) {
            const salt = await bcrypt.genSalt(10);
            user.passwordHash = await bcrypt.hash(user.passwordHash, salt);
          }
        },
        beforeUpdate: async (user) => {
          if (user.changed('passwordHash')) {
            const salt = await bcrypt.genSalt(10);
            user.passwordHash = await bcrypt.hash(user.passwordHash, salt);
          }
        },
      },
    });
    return User;
  }

  async validatePassword(password) {
    return bcrypt.compare(password, this.passwordHash);
  }
}

module.exports = User;
