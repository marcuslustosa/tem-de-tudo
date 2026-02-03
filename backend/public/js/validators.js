/**
 * VALIDADORES FRONTEND - TEM DE TUDO
 * Validações completas para formulários
 * 
 * @version 2.0.0
 * @author Tem de Tudo Team
 */

// ================================
// VALIDADORES
// ================================
const Validators = {
    /**
     * Validar email
     * @param {string} email - Email para validar
     */
    email(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    },

    /**
     * Validar senha
     * @param {string} password - Senha para validar
     * @param {number} minLength - Comprimento mínimo
     */
    password(password, minLength = 6) {
        return password && password.length >= minLength;
    },

    /**
     * Validar CPF
     * @param {string} cpf - CPF para validar
     */
    cpf(cpf) {
        cpf = cpf.replace(/[^\d]/g, '');

        if (cpf.length !== 11) return false;

        // Verificar se todos os dígitos são iguais
        if (/^(\d)\1{10}$/.test(cpf)) return false;

        // Validar dígitos verificadores
        let soma = 0;
        let resto;

        for (let i = 1; i <= 9; i++) {
            soma += parseInt(cpf.substring(i - 1, i)) * (11 - i);
        }

        resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.substring(9, 10))) return false;

        soma = 0;
        for (let i = 1; i <= 10; i++) {
            soma += parseInt(cpf.substring(i - 1, i)) * (12 - i);
        }

        resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.substring(10, 11))) return false;

        return true;
    },

    /**
     * Validar CNPJ
     * @param {string} cnpj - CNPJ para validar
     */
    cnpj(cnpj) {
        cnpj = cnpj.replace(/[^\d]/g, '');

        if (cnpj.length !== 14) return false;

        // Verificar se todos os dígitos são iguais
        if (/^(\d)\1{13}$/.test(cnpj)) return false;

        // Validar dígitos verificadores
        let tamanho = cnpj.length - 2;
        let numeros = cnpj.substring(0, tamanho);
        let digitos = cnpj.substring(tamanho);
        let soma = 0;
        let pos = tamanho - 7;

        for (let i = tamanho; i >= 1; i--) {
            soma += numeros.charAt(tamanho - i) * pos--;
            if (pos < 2) pos = 9;
        }

        let resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11);
        if (resultado != digitos.charAt(0)) return false;

        tamanho = tamanho + 1;
        numeros = cnpj.substring(0, tamanho);
        soma = 0;
        pos = tamanho - 7;

        for (let i = tamanho; i >= 1; i--) {
            soma += numeros.charAt(tamanho - i) * pos--;
            if (pos < 2) pos = 9;
        }

        resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11);
        if (resultado != digitos.charAt(1)) return false;

        return true;
    },

    /**
     * Validar telefone brasileiro
     * @param {string} phone - Telefone para validar
     */
    phone(phone) {
        phone = phone.replace(/[^\d]/g, '');
        return phone.length === 10 || phone.length === 11;
    },

    /**
     * Validar CEP
     * @param {string} cep - CEP para validar
     */
    cep(cep) {
        cep = cep.replace(/[^\d]/g, '');
        return cep.length === 8;
    },

    /**
     * Validar campo obrigatório
     * @param {string} value - Valor para validar
     */
    required(value) {
        return value !== null && value !== undefined && value.trim() !== '';
    },

    /**
     * Validar comprimento mínimo
     * @param {string} value - Valor para validar
     * @param {number} min - Comprimento mínimo
     */
    minLength(value, min) {
        return value && value.length >= min;
    },

    /**
     * Validar comprimento máximo
     * @param {string} value - Valor para validar
     * @param {number} max - Comprimento máximo
     */
    maxLength(value, max) {
        return value && value.length <= max;
    }
};

// ================================
// MÁSCARAS DE INPUT
// ================================
const InputMasks = {
    /**
     * Aplicar máscara de CPF
     * @param {string} value - Valor para mascarar
     */
    cpf(value) {
        return value
            .replace(/\D/g, '')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    },

    /**
     * Aplicar máscara de CNPJ
     * @param {string} value - Valor para mascarar
     */
    cnpj(value) {
        return value
            .replace(/\D/g, '')
            .replace(/(\d{2})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1/$2')
            .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
    },

    /**
     * Aplicar máscara de telefone
     * @param {string} value - Valor para mascarar
     */
    phone(value) {
        value = value.replace(/\D/g, '');
        if (value.length <= 10) {
            return value
                .replace(/(\d{2})(\d)/, '($1) $2')
                .replace(/(\d{4})(\d{1,4})$/, '$1-$2');
        } else {
            return value
                .replace(/(\d{2})(\d)/, '($1) $2')
                .replace(/(\d{5})(\d{1,4})$/, '$1-$2');
        }
    },

    /**
     * Aplicar máscara de CEP
     * @param {string} value - Valor para mascarar
     */
    cep(value) {
        return value
            .replace(/\D/g, '')
            .replace(/(\d{5})(\d{1,3})$/, '$1-$2');
    }
};

// ================================
// HELPER PARA APLICAR MÁSCARAS
// ================================
function applyMask(input, maskType) {
    input.addEventListener('input', (e) => {
        e.target.value = InputMasks[maskType](e.target.value);
    });
}

// Expor globalmente
window.Validators = Validators;
window.InputMasks = InputMasks;
window.applyMask = applyMask;

console.log('✅ Validators carregado');
