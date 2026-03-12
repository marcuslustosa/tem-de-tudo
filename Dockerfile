# Dockerfile para Railway/Render - API Node + front estático
FROM node:18-slim

WORKDIR /app

# Copia manifests da raiz (postinstall instala backend/api)
COPY package*.json ./

# Copia manifests da API para cache otimizado
COPY backend/api/package*.json backend/api/

# Instala dependências (raiz e API via postinstall)
RUN npm ci

# Copia código
# Copia API e demais assets do backend (HTML estático)
COPY backend ./backend

# Porta do serviço (Railway usa $PORT)
ENV PORT=3001

# Expor a porta para consistência local
EXPOSE 3001

# Start: aplica migrations e sobe server (já definido em package.json raiz -> backend/api start)
CMD ["npm", "start"]
