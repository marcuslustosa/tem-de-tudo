/**
 * Review.js - Modelo Sequelize para avaliações de empresas e serviços
 */

const { DataTypes, Model } = require('sequelize');

class Review extends Model {
  static initModel(sequelize) {
    Review.init({
      id: {
        type: DataTypes.INTEGER,
        primaryKey: true,
        autoIncrement: true,
      },
      userId: {
        type: DataTypes.INTEGER,
        allowNull: false,
      },
      companyId: {
        type: DataTypes.INTEGER,
        allowNull: false,
      },
      rating: {
        type: DataTypes.INTEGER,
        allowNull: false,
        validate: { min: 1, max: 5 },
      },
      comment: {
        type: DataTypes.TEXT,
        allowNull: true,
      },
    }, {
      sequelize,
      modelName: 'Review',
      tableName: 'reviews',
      timestamps: true,
    });
    return Review;
  }
}

module.exports = Review;
