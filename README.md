# APOTHIKA (MVP) — Plataforma SaaS White-Label de Fidelización Gamificada

Progressive Web App (PWA) de fidelización y bienestar preventivo para farmacias independientes en Argentina. Opera con un **único backend API REST** y **dos interfaces diferenciadas por rol**:

- **Paciente (B2C):** Interfaz *white-label* adaptada a la identidad visual de la farmacia (caso piloto: **Farmacia Dalton**), con credencial QR + DNI, saldo/historial de puntos, trivias educativas gamificadas y catálogo de cupones.
- **Farmacia (B2B):** Panel de gestión con identidad visual de **APOTHIKA**, escáner QR / buscador por DNI para acreditación de puntos, CRUD de recompensas, validador de cupones en caja y personalizador de marca blanca.

## Arquitectura del Repositorio (Monorepo)

```text
apothika-mvp/
├── apothika-api/   # Backend API REST (Laravel 11 + MySQL/MariaDB + JWT)
└── apothika-pwa/   # Frontend PWA (Vue 3 Composition API + Vite + Tailwind CSS)
```

## Requisitos Previos

- PHP >= 8.2 y MySQL / MariaDB (XAMPP)
- Composer
- Node.js (LTS) y NPM
- Git

## Instalación Local (Backend)

```bash
cd apothika-api
composer install
cp .env.example .env
php artisan key:generate
php artisan storage:link
php artisan migrate:fresh --seed
php artisan serve
```

> **Equipo:** Natalin Eliana Inchauspe & Julieta Mendieta — Escuela Da Vinci (DWN4AV).