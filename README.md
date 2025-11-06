# 🏫 School Timetable Demo — Laravel 12 + FastAPI (Google OR-Tools)

A complete demo that shows how to **generate**, **visualize**, and **edit** school timetables:

- **Laravel 12** — web UI + REST controller  
- **FastAPI + Google OR-Tools** — Python solver microservice  
- **Tailwind CSS + Vite** — clean modern UI  
- **ApexCharts** — workload & usage dashboards  
- **Drag & drop** editing with swap & constraint checks

---

## ✨ Key Features

- CP-SAT solver assigns subjects to timeslots and teachers under constraints
- Flip timetable orientation: **Days on X-axis**, **Periods on Y-axis**
- Drag-and-drop to move/swap lessons (server validates clashes)
- **Workload dashboard:** loads per teacher (+ CSV export)
- **Subject usage dashboard:** periods/week for a subject in a group
- **Curriculum rules:** exact weekly period requirements per grade & subject

---

## 🧰 Prerequisites

| Tool | Version |
|---|---|
| PHP | 8.2+ |
| Composer | 2.x |
| Node.js | 18+ |
| Python | 3.10+ |
| MySQL/MariaDB | 8+ |
| OR-Tools | latest wheels via `pip` |

> If `pip` complains about OR-Tools, upgrade pip: `python -m pip install --upgrade pip`

---

## 🚀 Quick Start

### 1) Clone & install

```bash
git clone https://github.com/your-org/timetable-demo.git
cd timetable-demo

composer install
npm install
