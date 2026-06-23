-- MIGRACIÓN 1: Creación de tablas de solicitudes de modificación (21 de Junio)
CREATE TABLE IF NOT EXISTS authorizer_users (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT UNIQUE NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS modification_requests (
    id BIGSERIAL PRIMARY KEY,
    type VARCHAR(255) NOT NULL,
    status VARCHAR(255) NOT NULL DEFAULT 'PENDIENTE',
    justification TEXT NOT NULL,
    requested_by BIGINT NOT NULL,
    authorized_by BIGINT NULL,
    rejected_by BIGINT NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (authorized_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (rejected_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS modification_request_items (
    id BIGSERIAL PRIMARY KEY,
    modification_request_id BIGINT NOT NULL,
    record_id BIGINT NOT NULL,
    original_data JSONB NOT NULL,
    new_data JSONB NOT NULL,
    action VARCHAR(255) NOT NULL DEFAULT 'MODIFICAR',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (modification_request_id) REFERENCES modification_requests(id) ON DELETE CASCADE
);

-- MIGRACIÓN 2: Insertar Menú de Autorizantes (Se omiten los inserts dinámicos complejos, puedes dejárselo a Laravel si es posible, pero aquí está la estructura de las nuevas columnas)

-- MIGRACIÓN 3: Columna is_migration en contracts (22 de Junio)
ALTER TABLE contracts
ADD COLUMN is_migration BOOLEAN NOT NULL DEFAULT FALSE;

-- MIGRACIÓN 4 y 5: Reestructuración de supplier_payments y concepts (22 de Junio)
ALTER TABLE supplier_payments
ADD COLUMN development_id BIGINT NULL,
ADD COLUMN plazo INT NULL,
ADD COLUMN fecha_inicio DATE NULL,
ADD COLUMN fecha_fin DATE NULL,
ADD COLUMN enganche NUMERIC(15, 2) NOT NULL DEFAULT 0;

ALTER TABLE supplier_payments ALTER COLUMN payment_method_id DROP NOT NULL;
ALTER TABLE supplier_payments ALTER COLUMN fecha DROP NOT NULL;

ALTER TABLE supplier_payment_concepts
ADD COLUMN fecha DATE NULL,
ADD COLUMN payment_method_id BIGINT NULL;

-- MIGRACIÓN 6: Campos nuevos en creditor_vouchers (22 de Junio)
ALTER TABLE creditor_vouchers
ADD COLUMN enganche NUMERIC(12, 2) NOT NULL DEFAULT 0,
ADD COLUMN num_socios INT NOT NULL DEFAULT 2,
ADD COLUMN fecha_inicio DATE NULL,
ADD COLUMN fecha_fin DATE NULL;

-- MIGRACIÓN 7: Campos nuevos en creditor_voucher_items (22 de Junio)
ALTER TABLE creditor_voucher_items
ADD COLUMN fecha_pago_programada DATE NULL,
ADD COLUMN cantidad_a_pagar NUMERIC(12, 2) NOT NULL DEFAULT 0,
ADD COLUMN interes_pagado NUMERIC(12, 2) NOT NULL DEFAULT 0,
ADD COLUMN observaciones TEXT NULL;
-- INSERTS PARA MENÚS NUEVOS
INSERT INTO menus (id, nombre, clave, ruta, icono, parent_id, orden, es_menu, status_id, created_at, updated_at) VALUES (6, 'Acreedores', 'acreedores_root', NULL, 'fa-solid fa-hand-holding-dollar', NULL, 6, true, 1, NOW(), NOW()) ON CONFLICT (id) DO NOTHING;
INSERT INTO role_menu_permissions (role_id, menu_id, can_view, can_create, can_update, can_delete, created_at, updated_at) VALUES (1, 6, true, true, true, true, NOW(), NOW()) ON CONFLICT DO NOTHING;
INSERT INTO menus (id, nombre, clave, ruta, icono, parent_id, orden, es_menu, status_id, created_at, updated_at) VALUES (36, 'Modificaciones masivas', 'bulk_modifications_module', 'modificaciones-masivas', 'fa-solid fa-file-signature', 4, 8, true, 1, NOW(), NOW()) ON CONFLICT (id) DO NOTHING;
INSERT INTO role_menu_permissions (role_id, menu_id, can_view, can_create, can_update, can_delete, created_at, updated_at) VALUES (1, 36, true, true, true, true, NOW(), NOW()) ON CONFLICT DO NOTHING;
INSERT INTO menus (id, nombre, clave, ruta, icono, parent_id, orden, es_menu, status_id, created_at, updated_at) VALUES (37, 'Autorizantes', 'authorizers_module', 'autorizantes', 'fa-solid fa-user-shield', 4, 9, true, 1, NOW(), NOW()) ON CONFLICT (id) DO NOTHING;
INSERT INTO role_menu_permissions (role_id, menu_id, can_view, can_create, can_update, can_delete, created_at, updated_at) VALUES (1, 37, true, true, true, true, NOW(), NOW()) ON CONFLICT DO NOTHING;
INSERT INTO menus (id, nombre, clave, ruta, icono, parent_id, orden, es_menu, status_id, created_at, updated_at) VALUES (30, 'Acreedores', 'creditors_module', 'acreedores', 'fa-solid fa-users-line', 6, 1, true, 1, NOW(), NOW()) ON CONFLICT (id) DO NOTHING;
INSERT INTO role_menu_permissions (role_id, menu_id, can_view, can_create, can_update, can_delete, created_at, updated_at) VALUES (1, 30, true, true, true, true, NOW(), NOW()) ON CONFLICT DO NOTHING;
INSERT INTO menus (id, nombre, clave, ruta, icono, parent_id, orden, es_menu, status_id, created_at, updated_at) VALUES (31, 'Pagos acreedores', 'creditor_payments_module', 'pagos-acreedores', 'fa-solid fa-money-bills', 6, 2, true, 1, NOW(), NOW()) ON CONFLICT (id) DO NOTHING;
INSERT INTO role_menu_permissions (role_id, menu_id, can_view, can_create, can_update, can_delete, created_at, updated_at) VALUES (1, 31, true, true, true, true, NOW(), NOW()) ON CONFLICT DO NOTHING;
INSERT INTO menus (id, nombre, clave, ruta, icono, parent_id, orden, es_menu, status_id, created_at, updated_at) VALUES (32, 'Abonos acreedores', 'creditor_voucher_payments_module', 'abonos-acreedores', 'fa-solid fa-cash-register', 6, 3, true, 1, NOW(), NOW()) ON CONFLICT (id) DO NOTHING;
INSERT INTO role_menu_permissions (role_id, menu_id, can_view, can_create, can_update, can_delete, created_at, updated_at) VALUES (1, 32, true, true, true, true, NOW(), NOW()) ON CONFLICT DO NOTHING;
