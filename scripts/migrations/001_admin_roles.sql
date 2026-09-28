ALTER TABLE administradores
    ADD COLUMN perfil ENUM('superadmin', 'admin')
    NOT NULL DEFAULT 'admin'
    AFTER senha_hash;

UPDATE administradores
SET perfil = 'superadmin'
ORDER BY id ASC
LIMIT 1;