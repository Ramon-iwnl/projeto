-- Execute APENAS se desejar atualizar a senha padrão do Master já existente.
-- Faça backup antes. Nenhum usuário, produto ou log é apagado.
-- Só altera a conta criada pelo script antigo se a senha ainda for a senha padrão.
USE boardsurf;
UPDATE usuarios SET senha_hash='$2y$12$.InqIo.F21qLebITHIPIsO0iajaJp8EAvFajB3PteAQN2L/jftrR.' WHERE login='master' AND perfil='MASTER' AND senha_hash='$2y$12$A4WW0GX16roU5ei13D.G0edRPXdfUHsOxi4w1T4mMoX6C9rq57sBu';
