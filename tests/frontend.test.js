/**
 * frontend.test.js - Testes automatizados para frontend usando Jest e Puppeteer
 */

const puppeteer = require('puppeteer');

describe('Testes Frontend Tem de Tudo', () => {
  let browser;
  let page;

  beforeAll(async () => {
    browser = await puppeteer.launch({
      headless: true,
      args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    page = await browser.newPage();
  }, 30000);

  afterAll(async () => {
    if (browser) {
      try {
        await browser.close();
      } catch (error) {
        console.error('Erro ao fechar o browser:', error);
      }
    }
  }, 60000);

  test('Página inicial carrega corretamente', async () => {
    await page.goto('file://' + __dirname + '/../frontend/index.html');
    const title = await page.title();
    expect(title).toBe('Tem de Tudo - Plataforma de Fidelidade');
    const headerText = await page.$eval('header .logo img', el => el.alt);
    expect(headerText).toBe('Tem de Tudo Logo');
  });

  test('Menu contém links principais', async () => {
    await page.goto('file://' + __dirname + '/../frontend/index.html');
    const links = await page.$$eval('.nav-links li a', els => els.map(el => el.textContent));
    expect(links).toEqual(expect.arrayContaining(['Início', 'Benefícios', 'Login', 'Cadastro']));
  });

  test('Botão de menu toggle funciona em mobile', async () => {
    await page.setViewport({ width: 500, height: 800 });
    await page.goto('file://' + __dirname + '/../frontend/index.html');
    await page.click('.menu-toggle');
    const navVisible = await page.$eval('.nav-links', el => window.getComputedStyle(el).display !== 'none');
    expect(navVisible).toBe(true);
  });
});
