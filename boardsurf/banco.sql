CREATE DATABASE IF NOT EXISTS boardsurf CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE boardsurf;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(80) NOT NULL,
  data_nascimento DATE NOT NULL,
  sexo VARCHAR(30) NOT NULL,
  nome_materno VARCHAR(80) NOT NULL,
  cpf CHAR(11) NOT NULL UNIQUE,
  email VARCHAR(120) NOT NULL,
  celular CHAR(11) NOT NULL,
  telefone_fixo CHAR(10) NOT NULL,
  cep CHAR(8) NOT NULL,
  logradouro VARCHAR(150) NOT NULL,
  numero VARCHAR(20) NOT NULL,
  bairro VARCHAR(100) NOT NULL,
  cidade VARCHAR(100) NOT NULL,
  estado CHAR(2) NOT NULL,
  login VARCHAR(20) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  perfil ENUM('MASTER','COMUM') NOT NULL DEFAULT 'COMUM',
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS logs_autenticacao (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NULL,
  login VARCHAR(20) NULL,
  nome VARCHAR(80) NULL,
  cpf CHAR(11) NULL,
  evento VARCHAR(50) NOT NULL,
  resultado VARCHAR(40) NOT NULL,
  fator_autenticacao VARCHAR(30) NOT NULL,
  ip VARCHAR(45) NULL,
  data_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_logs_data (data_hora),
  INDEX idx_logs_login (login),
  INDEX idx_logs_cpf (cpf),
  CONSTRAINT fk_logs_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Usuário Master criado diretamente no banco, como pede a especificação.
-- Login: master | Senha: MasterAb
INSERT INTO usuarios (nome,data_nascimento,sexo,nome_materno,cpf,email,celular,telefone_fixo,cep,logradouro,numero,bairro,cidade,estado,login,senha_hash,perfil,ativo)
SELECT 'Administrador Master','2000-01-01','Outro','Administrador','00000000191','master@boardsurf.com.br','21999999999','2122222222','20000000','Rua BoardSurf','1','Centro','Rio de Janeiro','RJ','master','$2y$12$.InqIo.F21qLebITHIPIsO0iajaJp8EAvFajB3PteAQN2L/jftrR.','MASTER',1
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE login='master');


CREATE TABLE IF NOT EXISTS produtos (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(150) NOT NULL, categoria VARCHAR(30) NOT NULL, preco DECIMAL(10,2) NOT NULL, imagem VARCHAR(255) NOT NULL UNIQUE, cor VARCHAR(40) DEFAULT '', avaliacao VARCHAR(50) DEFAULT '', estoque INT UNSIGNED NOT NULL DEFAULT 10, ativo TINYINT(1) NOT NULL DEFAULT 1);
INSERT INTO produtos (nome,categoria,preco,imagem,cor,avaliacao) VALUES ('REGATA LOGO ELIPSE RED','regatas',159.99,'assets/img/regata1.webp','red','★★★★★') ON DUPLICATE KEY UPDATE nome=VALUES(nome),categoria=VALUES(categoria);
INSERT INTO produtos (nome,categoria,preco,imagem,cor,avaliacao) VALUES ('REGATA LOGO ELIPSE BLACK','regatas',159.99,'assets/img/regata2.webp','black','★★★★★') ON DUPLICATE KEY UPDATE nome=VALUES(nome),categoria=VALUES(categoria);
INSERT INTO produtos (nome,categoria,preco,imagem,cor,avaliacao) VALUES ('REGATA LOGO ELIPSE WHITE','regatas',159.99,'assets/img/regata3.webp','white','★★★★★') ON DUPLICATE KEY UPDATE nome=VALUES(nome),categoria=VALUES(categoria);
INSERT INTO produtos (nome,categoria,preco,imagem,cor,avaliacao) VALUES ('REGATA LOGO ELIPSE BLUE','regatas',159.99,'assets/img/regata4.webp','blue','★★★★★') ON DUPLICATE KEY UPDATE nome=VALUES(nome),categoria=VALUES(categoria);
INSERT INTO produtos (nome,categoria,preco,imagem,cor,avaliacao) VALUES ('CAMISETA LOGO ELIPSE WHITE','camisetas',179.99,'assets/img/camiseta1.webp','white','★★★★★') ON DUPLICATE KEY UPDATE nome=VALUES(nome),categoria=VALUES(categoria);
INSERT INTO produtos (nome,categoria,preco,imagem,cor,avaliacao) VALUES ('CAMISETA LOGO ELIPSE BLACK','camisetas',179.99,'assets/img/camiseta2.webp','black','★★★★★') ON DUPLICATE KEY UPDATE nome=VALUES(nome),categoria=VALUES(categoria);
INSERT INTO produtos (nome,categoria,preco,imagem,cor,avaliacao) VALUES ('SHORT RUNNER GRAY','bermudas',349.99,'assets/img/bermuda1.webp','gray','★★★★★') ON DUPLICATE KEY UPDATE nome=VALUES(nome),categoria=VALUES(categoria);
INSERT INTO produtos (nome,categoria,preco,imagem,cor,avaliacao) VALUES ('SHORT RUNNER GREEN','bermudas',349.99,'assets/img/bermuda2.webp','greenyellow','★★★★★') ON DUPLICATE KEY UPDATE nome=VALUES(nome),categoria=VALUES(categoria);
INSERT INTO produtos (nome,categoria,preco,imagem,cor,avaliacao) VALUES ('SHORT RUNNER BLACK','bermudas',349.99,'assets/img/bermuda3.webp','black','★★★★★') ON DUPLICATE KEY UPDATE nome=VALUES(nome),categoria=VALUES(categoria);
INSERT INTO produtos (nome,categoria,preco,imagem,cor,avaliacao) VALUES ('SHORT RUNNER BLUE','bermudas',349.99,'assets/img/bermuda4.webp','blue','★★★★★') ON DUPLICATE KEY UPDATE nome=VALUES(nome),categoria=VALUES(categoria);
INSERT INTO produtos (nome,categoria,preco,imagem,cor,avaliacao) VALUES ('NIKE COURT VISION LOW','tenis',398.99,'assets/img/tenis 1.avif','black','★★★★★') ON DUPLICATE KEY UPDATE nome=VALUES(nome),categoria=VALUES(categoria);
INSERT INTO produtos (nome,categoria,preco,imagem,cor,avaliacao) VALUES ('NIKE AIR FORCE 1 07','tenis',759.99,'assets/img/tenis 2.avif','white','★★★★★') ON DUPLICATE KEY UPDATE nome=VALUES(nome),categoria=VALUES(categoria);
INSERT INTO produtos (nome,categoria,preco,imagem,cor,avaliacao) VALUES ('NIKE AIR MAX 90','tenis',1099.99,'assets/img/tenis 3.avif','white','★★★★★') ON DUPLICATE KEY UPDATE nome=VALUES(nome),categoria=VALUES(categoria);
INSERT INTO produtos (nome,categoria,preco,imagem,cor,avaliacao) VALUES ('NIKE AIR MAX 90','tenis',1099.99,'assets/img/tenis 4.avif','white','★★★★★') ON DUPLICATE KEY UPDATE nome=VALUES(nome),categoria=VALUES(categoria);
