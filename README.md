# Sistema de Gestión de Actividades y Créditos Extracurriculares

## Descripción

Sistema web desarrollado para la gestión y administración de actividades extracurriculares y créditos académicos.

La aplicación permite centralizar procesos que anteriormente se realizaban de forma manual, facilitando el registro, consulta, administración y generación de constancias relacionadas con las actividades extracurriculares de los estudiantes.

> **Nota:** Este repositorio corresponde a una versión preparada para fines de portafolio. Por razones de seguridad, privacidad y confidencialidad institucional, no se incluyen datos reales, credenciales, configuraciones sensibles ni información académica de estudiantes.

---

## ¿Por qué se desarrolló?

El proyecto surgió con el objetivo de mejorar y digitalizar procesos administrativos relacionados con el control de actividades extracurriculares.

Anteriormente, parte del proceso dependía de registros y comprobantes físicos, lo que podía dificultar la consulta de información, el seguimiento de actividades y la generación de constancias.

El sistema busca centralizar esta información y proporcionar una herramienta que facilite la gestión tanto para los administradores como para los estudiantes.

---

## 🏛️ Contexto del proyecto

El sistema fue desarrollado como parte de un proyecto de **Ejercicio Profesional Supervisado (EPS)** para la **Facultad de Arquitectura de la Universidad de San Carlos de Guatemala (USAC)**.

El desarrollo estuvo orientado a atender necesidades reales de gestión académica y administrativa relacionadas con las actividades extracurriculares.

---

## Arquitectura

El proyecto utiliza una **arquitectura monolítica en capas**, organizada para separar las responsabilidades principales de la aplicación.

### Patrón MVC

Se utiliza el patrón **Modelo-Vista-Controlador (MVC)** proporcionado por Laminas.

```text
┌───────────────────────────┐
│          Usuario          │
└─────────────┬─────────────┘
              │
              ▼
┌───────────────────────────┐
│       Controladores       │
│      (Controllers)        │
└─────────────┬─────────────┘
              │
              ▼
┌───────────────────────────┐
│       Servicios           │
│        (Services)         │
└─────────────┬─────────────┘
              │
              ▼
┌───────────────────────────┐
│        Modelos            │
│    TableGateway / DB      │
└─────────────┬─────────────┘
              │
              ▼
┌───────────────────────────┐
│         MySQL             │
└───────────────────────────┘
```

### Capas principales

* **Presentación:** vistas PHTML y componentes de interfaz.
* **Controladores:** reciben y procesan las solicitudes HTTP.
* **Servicios:** contienen la lógica de negocio.
* **Modelos:** gestionan el acceso a los datos.
* **Persistencia:** bases de datos utilizadas por el sistema.

---

## 🛠️ Tecnologías utilizadas

### Backend

* **PHP 8.3** — Lenguaje de programación.
* **Laminas MVC** — Framework para el desarrollo de la aplicación.
* **Laminas DB** — Acceso y gestión de datos.
* **Composer** — Gestión de dependencias.

### Frontend

* **PHTML** — Vistas y presentación.
* **HTML5**
* **CSS3**
* **JavaScript**
* **Bootstrap** — Componentes y estilos de interfaz.

### Base de datos

* **MySQL** — Sistema gestor de bases de datos.

### Pruebas

* **PHPUnit** — Pruebas automatizadas.
* Pruebas unitarias e integración de componentes.

### Control de versiones

* **Git**
* **GitHub**

---

##  Principales funcionalidades

Entre las funcionalidades desarrolladas se encuentran:

* Gestión de usuarios.
* Gestión de roles y permisos.
* Gestión de actividades extracurriculares.
* Registro de participación de estudiantes.
* Consulta de actividades y créditos.
* Generación de reportes.
* Registro de auditoría de operaciones.
* Recuperación de contraseña mediante correo electrónico.
* Validaciones relacionadas con estudiantes y actividades.
* Pruebas automatizadas de diferentes componentes del sistema.

---

## Seguridad y privacidad

Debido a que el sistema fue desarrollado para una institución educativa, esta versión pública no contiene:

* Datos reales de estudiantes.
* Credenciales de acceso.
* Contraseñas.
* Claves de aplicaciones.
* Tokens.
* Configuraciones sensibles.
* Bases de datos institucionales.
* Documentos académicos reales.

Las configuraciones necesarias para ejecutar el proyecto deben definirse de manera local.

---

## Pruebas

El proyecto utiliza PHPUnit para validar el comportamiento de diferentes componentes de la aplicación.

Las pruebas incluyen validaciones de:

* Controladores.
* Servicios.
* Modelos.
* Operaciones de base de datos.
* Casos exitosos y casos de error.

---

##  Estructura general

```text
├── config/
├── module/
│   └── Application/
│       ├── src/
│       │   ├── Controller/
│       │   ├── Model/
│       │   └── Service/
│       ├── view/
│       └── test/
├── public/
├── vendor/
├── composer.json
├── composer.lock
└── README.md
```

> La estructura puede variar dependiendo de la configuración utilizada en cada entorno.

---

##  Instalación

### Requisitos

* PHP 8.3 o superior
* Composer
* MySQL
* Apache o servidor web compatible

### Instalación de dependencias

```bash
composer install
```

Posteriormente deben configurarse las conexiones de base de datos de acuerdo con el entorno local.

---

## Contexto académico

**Proyecto:** Sistema de Gestión de Actividades y Créditos Extracurriculares
**Modalidad:** Ejercicio Profesional Supervisado (EPS)
**Institución:** Universidad de San Carlos de Guatemala
**Unidad académica:** Facultad de Arquitectura

El proyecto fue desarrollado como parte de un proceso de análisis, diseño, desarrollo, pruebas e implementación de una solución informática orientada a una necesidad institucional real.

---

## Autor

**Luis Cutzal**

Proyecto desarrollado como parte de mi formación profesional en el área de Ingeniería en Ciencias y Sistemas.

---

## ⚠️ Aviso

Este repositorio tiene fines principalmente **académicos y de portafolio profesional**.

El código publicado corresponde a una versión preparada para su distribución pública y no representa necesariamente la configuración utilizada en el entorno institucional original.
