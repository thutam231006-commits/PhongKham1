# AGENT RULES FOR MIND GUIDE (PROJECT: PHONGKHAM)

## 1. Project Context & Source of Truth
Read and strictly follow these project-specific documents in `docs/`:
- `docs/MASTER-SRS-BA-STANDARDIZED.md` (Business Logic & Feature Requirements)
- `docs/ThietKeGiaoDien.md` (UI/UX Specifications for 51 Screens SCR-*)
- `docs/MG-Tai-lieu-tong-hop-quy-trinh.md` (End-to-End Workflows & Payment/Refund Rules)

## 2. Engineering Standards (ECC Framework)
- Refer to and adhere to the coding standards, folder structure, and Git conventions in the cloned ECC repository inside this project.

## 3. Brand Identity & Visual System (Mind Guide)
- **Brand Name:** Mind Guide
- **Primary Color:** Deep Teal `#1D4B43` (`mg-primary`)
- **Secondary Color:** Sage Green `#709782` (`mg-secondary`)
- **Background Color:** Warm Off-White `#F7FAFC` (`mg-bg`)
- **Text Color:** Slate `#1A202C` (`mg-text`)
- **Safety Banner (NFR-USA-003):** Emergency warning banner (`#FFEDD5` bg, `#7C2D12` text, hotline 115 / 1900 1567) MUST appear below the header on public and customer views.

## 4. Execution Directives
- Build all UI components and pages using Laravel Blade (`resources/views/`) and Tailwind CSS.
- Ensure 4 mandatory data states for all screens: Loading, Empty, Error, Success.
- Hide sensitive fields (`customer_note`, consultation records) from Staff/Admin according to `ThietKeGiaoDien.md`.
- Never store HTML raw inputs; always escape output for security (NFR-SEC-004).