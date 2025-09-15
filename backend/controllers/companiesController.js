/**
 * companiesController.js - Controlador para gerenciamento de empresas
 * Segue padrões PSR-12 adaptados para JavaScript, código limpo e seguro.
 */

const { Company } = require('../models');
const { Op } = require('sequelize');

class CompaniesController {
  // Solicitar cadastro de empresa
  static async requestCompany(req, res) {
    try {
      const { name, email, phone, address } = req.body;

      if (!name || !email) {
        return res.status(400).json({ error: 'Nome e email são obrigatórios.' });
      }

      // Verificar se empresa já existe
      const existing = await Company.findOne({ where: { email } });
      if (existing) {
        return res.status(409).json({ error: 'Empresa já cadastrada.' });
      }

      const company = await Company.create({
        name,
        email,
        phone,
        address,
        approved: false,
        paymentConfirmed: false,
      });

      return res.status(201).json({ message: 'Cadastro solicitado com sucesso.', companyId: company.id });
    } catch (error) {
      console.error('Erro ao solicitar cadastro de empresa:', error);
      return res.status(500).json({ error: 'Erro interno do servidor.' });
    }
  }

  // Listar empresas (aprovadas ou todas, com filtro opcional)
  static async listCompanies(req, res) {
    try {
      const { approved } = req.query;
      const where = {};

      if (approved !== undefined) {
        where.approved = approved === 'true';
      }

      const companies = await Company.findAll({ where });
      return res.json(companies);
    } catch (error) {
      console.error('Erro ao listar empresas:', error);
      return res.status(500).json({ error: 'Erro interno do servidor.' });
    }
  }

  // Aprovar empresa (perfil master)
  static async approveCompany(req, res) {
    try {
      const { id } = req.params;

      const company = await Company.findByPk(id);
      if (!company) {
        return res.status(404).json({ error: 'Empresa não encontrada.' });
      }

      if (!company.paymentConfirmed) {
        return res.status(400).json({ error: 'Pagamento não confirmado. Não é possível aprovar.' });
      }

      company.approved = true;
      await company.save();

      return res.status(200).json({ message: 'Empresa aprovada e liberada para anunciar.' });
    } catch (error) {
      console.error('Erro ao aprovar empresa:', error);
      return res.status(500).json({ error: 'Erro interno do servidor.' });
    }
  }

  // Confirmar pagamento da empresa
  static async confirmPayment(req, res) {
    try {
      const { id } = req.params;

      const company = await Company.findByPk(id);
      if (!company) {
        return res.status(404).json({ error: 'Empresa não encontrada.' });
      }

      company.paymentConfirmed = true;
      await company.save();

      return res.json({ message: 'Pagamento confirmado para a empresa.', company });
    } catch (error) {
      console.error('Erro ao confirmar pagamento:', error);
      return res.status(500).json({ error: 'Erro interno do servidor.' });
    }
  }
}

module.exports = CompaniesController;
