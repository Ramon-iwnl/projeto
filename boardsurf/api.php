<?php
require_once __DIR__ . '/config.php';
start_app_session();

$action = $_GET['action'] ?? '';

try {
    $db = get_db();

    switch ($action) {

        case 'admin_products':
            require_method('GET');
            require_master();
            if (empty($_SESSION['products_csrf'])) $_SESSION['products_csrf'] = bin2hex(random_bytes(32));
            $rows = $db->query('SELECT id,nome,categoria,preco,imagem,cor,avaliacao,estoque,ativo FROM produtos ORDER BY id DESC')->fetchAll();
            json_response(['ok'=>true,'products'=>$rows,'csrf'=>$_SESSION['products_csrf']]);

        case 'save_product':
            require_method('POST');
            require_master();
            $d = input_json();
            if (!hash_equals($_SESSION['products_csrf'] ?? '', (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) || empty($_SESSION['products_csrf']))
                json_response(['ok'=>false,'message'=>'Sessão expirada. Atualize o painel.'],403);
            $id = filter_var($d['id'] ?? 0,FILTER_VALIDATE_INT);
            $id = $id && $id > 0 ? $id : 0;
            $nome = trim((string)($d['nome'] ?? ''));
            $categoria = trim((string)($d['categoria'] ?? ''));
            $imagem = trim((string)($d['imagem'] ?? ''));
            $cor = trim((string)($d['cor'] ?? ''));
            $avaliacao = trim((string)($d['avaliacao'] ?? ''));
            $preco = filter_var($d['preco'] ?? null,FILTER_VALIDATE_FLOAT);
            $estoque = filter_var($d['estoque'] ?? null,FILTER_VALIDATE_INT);
            $ativo = !empty($d['ativo']) ? 1 : 0;
            if ($nome === '' || mb_strlen($nome)>150 || !in_array($categoria,['camisetas','bermudas','tenis','regatas'],true))
                json_response(['ok'=>false,'message'=>'Nome ou categoria inválidos.'],422);
            if ($preco === false || $preco < 0 || $preco > 99999999 || $estoque === false || $estoque < 0 || $estoque > 1000000)
                json_response(['ok'=>false,'message'=>'Preço ou estoque inválidos.'],422);
            if (strlen($imagem)>255 || !preg_match('~^assets/img/[a-zA-Z0-9 _.-]+\.(?:webp|png|jpe?g|avif)$~i',$imagem) || str_contains($imagem,'..'))
                json_response(['ok'=>false,'message'=>'Imagem deve estar em assets/img/ e ter extensão válida.'],422);
            if (mb_strlen($cor)>40 || mb_strlen($avaliacao)>50)
                json_response(['ok'=>false,'message'=>'Cor ou avaliação muito longa.'],422);
            if ($id) {
                $stmt=$db->prepare('UPDATE produtos SET nome=?,categoria=?,preco=?,imagem=?,cor=?,avaliacao=?,estoque=?,ativo=? WHERE id=?');
                $stmt->execute([$nome,$categoria,$preco,$imagem,$cor,$avaliacao,$estoque,$ativo,$id]);
                if (!$stmt->rowCount()) {
                    $check=$db->prepare('SELECT id FROM produtos WHERE id=?');$check->execute([$id]);
                    if (!$check->fetch()) json_response(['ok'=>false,'message'=>'Produto não encontrado.'],404);
                }
            } else {
                $stmt=$db->prepare('INSERT INTO produtos (nome,categoria,preco,imagem,cor,avaliacao,estoque,ativo) VALUES (?,?,?,?,?,?,?,?)');
                $stmt->execute([$nome,$categoria,$preco,$imagem,$cor,$avaliacao,$estoque,$ativo]);
                $id=(int)$db->lastInsertId();
            }
            json_response(['ok'=>true,'message'=>'Produto salvo com sucesso.','id'=>$id]);

        case 'delete_product':
            require_method('POST');
            require_master();
            $d=input_json();
            if (!hash_equals($_SESSION['products_csrf'] ?? '', (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) || empty($_SESSION['products_csrf']))
                json_response(['ok'=>false,'message'=>'Sessão expirada. Atualize o painel.'],403);
            $id=filter_var($d['id'] ?? null,FILTER_VALIDATE_INT);
            if (!$id || $id<1) json_response(['ok'=>false,'message'=>'ID inválido.'],422);
            $stmt=$db->prepare('DELETE FROM produtos WHERE id=?');
            $stmt->execute([$id]);
            if (!$stmt->rowCount()) json_response(['ok'=>false,'message'=>'Produto não encontrado.'],404);
            json_response(['ok'=>true,'message'=>'Produto excluído.']);

        case 'products':
            require_method('GET');
            $categoria = trim((string)($_GET['categoria'] ?? ''));
            $stmt = $db->prepare('SELECT id,nome,categoria,preco,imagem,cor,avaliacao,estoque FROM produtos WHERE ativo=1 AND (? = \'\' OR categoria=?) ORDER BY id');
            $stmt->execute([$categoria,$categoria]);
            json_response(['ok'=>true,'products'=>$stmt->fetchAll()]);

        case 'health':
            $db->query('SELECT 1');
            $tables = [];
            foreach (['usuarios', 'logs_autenticacao', 'produtos'] as $table) {
                $q = $db->query("SHOW TABLES LIKE '$table'");
                $tables[$table] = (bool)$q->fetchColumn();
            }
            json_response([
                'ok' => true,
                'message' => 'PHP, MySQL e banco BoardSurf estão acessíveis.',
                'php' => PHP_VERSION,
                'database' => 'boardsurf',
                'tables' => $tables
            ]);

        case 'me':
            $user = current_user();
            json_response(['ok' => true, 'authenticated' => (bool)$user, 'user' => $user ? clean_user($user) : null]);

        case 'register':
            require_method('POST');
            $d = input_json();
            // Normaliza os valores recebidos. Se algum campo vier como array
            // (por exemplo, por causa de um input antigo com []), usa o único
            // valor quando houver apenas um; assim evitamos os warnings do PHP.
            $required = ['nome','dataNascimento','sexo','materno','cpf','email','celular','telefoneFixo','cep','logradouro','numero','bairro','cidade','estado','login','senha','confirmarSenha'];
            foreach ($required as $field) {
                $value = $d[$field] ?? '';
                if (is_array($value)) {
                    if (count($value) === 1) $value = reset($value);
                    else json_response(['ok'=>false,'message'=>"O campo {$field} recebeu vários valores. Verifique o formulário e tente novamente."], 422);
                }
                $d[$field] = is_scalar($value) ? trim((string)$value) : '';
                if ($d[$field] === '') json_response(['ok'=>false,'message'=>"Preencha o campo {$field}."], 422);
            }

            $login = $d['login'];
            $senha = $d['senha'];
            $cpf = preg_replace('/\D/', '', $d['cpf']);
            $celular = telefone_nacional($d['celular']);
            $telefone = telefone_nacional($d['telefoneFixo']);
            $cep = preg_replace('/\D/', '', $d['cep']);

            if (!nome_valido($d['nome'])) json_response(['ok'=>false,'message'=>'Nome deve ter entre 15 e 80 caracteres, somente letras e espaços.'],422);
            if (!letras($login, 6)) json_response(['ok'=>false,'message'=>'Login deve ter exatamente 6 letras.'],422);
            if (!letras($senha, 8)) json_response(['ok'=>false,'message'=>'A senha deve ter exatamente 8 letras.'],422);
            if ($senha !== $d['confirmarSenha']) json_response(['ok'=>false,'message'=>'As senhas não coincidem.'],422);
            if (!cpf_valido($cpf)) json_response(['ok'=>false,'message'=>'CPF inválido.'],422);
            if (!in_array(strlen($celular), [10,11], true)) json_response(['ok'=>false,'message'=>'Celular inválido.'],422);
            if (strlen($telefone) !== 10) json_response(['ok'=>false,'message'=>'Telefone fixo inválido.'],422);
            if (strlen($cep) !== 8 || $cep === '00000000') json_response(['ok'=>false,'message'=>'CEP inválido.'],422);
            if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) json_response(['ok'=>false,'message'=>'E-mail inválido.'],422);

            $nascimento = DateTimeImmutable::createFromFormat('!Y-m-d', $d['dataNascimento']);
            if (!$nascimento || $nascimento->format('Y-m-d') !== $d['dataNascimento'] || $nascimento > new DateTimeImmutable('today')) json_response(['ok'=>false,'message'=>'Data de nascimento inválida.'],422);
            if (!in_array($d['sexo'], ['Masculino','Feminino','Outro'], true)) json_response(['ok'=>false,'message'=>'Selecione um sexo válido.'],422);
            if (!preg_match('/^\p{L}+(?: +\p{L}+)*$/u', $d['materno']) || mb_strlen($d['materno']) > 80) json_response(['ok'=>false,'message'=>'Nome materno inválido. Use letras e espaços.'],422);
            $d['estado'] = strtoupper($d['estado']);
            if (!preg_match('/^[A-Z]{2}$/', $d['estado'])) json_response(['ok'=>false,'message'=>'Use a sigla do estado com duas letras.'],422);
            foreach (['email'=>120,'logradouro'=>150,'numero'=>20,'bairro'=>100,'cidade'=>100] as $campo=>$limite) {
                if (mb_strlen($d[$campo]) > $limite) json_response(['ok'=>false,'message'=>"O campo {$campo} excede o tamanho permitido."],422);
            }

            $check = $db->prepare('SELECT id FROM usuarios WHERE login = ? OR cpf = ? LIMIT 1');
            $check->execute([$login, $cpf]);
            if ($check->fetch()) json_response(['ok'=>false,'message'=>'Login ou CPF já cadastrado.'],409);

            $stmt = $db->prepare('INSERT INTO usuarios (nome,data_nascimento,sexo,nome_materno,cpf,email,celular,telefone_fixo,cep,logradouro,numero,bairro,cidade,estado,login,senha_hash,perfil,ativo,criado_em) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,\'COMUM\',1,NOW())');
            $stmt->execute([
                $d['nome'], $d['dataNascimento'], $d['sexo'], $d['materno'], $cpf,
                $d['email'], $celular, $telefone, $cep, $d['logradouro'], $d['numero'],
                $d['bairro'], $d['cidade'], $d['estado'], $login, password_hash($senha, PASSWORD_DEFAULT)
            ]);
            audit($db, (int)$db->lastInsertId(), $login, trim($d['nome']), 'CADASTRO', 'SUCESSO', 'N/A');
            json_response(['ok'=>true,'message'=>'Cadastro realizado com sucesso.']);

        case 'login':
            require_method('POST');
            $d = input_json();
            $login = trim((string)($d['login'] ?? ''));
            $senha = (string)($d['senha'] ?? '');
            if ($login === '' || $senha === '') json_response(['ok'=>false,'message'=>'Informe login e senha válidos.'],422);

            $stmt = $db->prepare('SELECT * FROM usuarios WHERE login = ? AND ativo = 1 LIMIT 1');
            $stmt->execute([$login]);
            $user = $stmt->fetch();
            if (!$user || !password_verify($senha, $user['senha_hash'])) {
                audit($db, $user ? (int)$user['id'] : null, $login, $user['nome'] ?? null, 'LOGIN', 'FALHA', 'SENHA');
                json_response(['ok'=>false,'message'=>'Usuário ou senha inválidos.'],401);
            }

            unset($_SESSION['user']);
            session_regenerate_id(true);
            $fatores = ['NOME_MATERNO'=>'nome_materno', 'DATA_NASCIMENTO'=>'data_nascimento', 'CEP'=>'cep'];
            $fator = array_keys($fatores)[random_int(0, 2)];
            $_SESSION['pending_2fa'] = [
                'user_id'=>(int)$user['id'], 'login'=>$user['login'], 'nome'=>$user['nome'],
                'fator'=>$fator, 'tentativas'=>0, 'expires'=>time()+300,
                'answer_hash'=>password_hash(normalizar_resposta($user[$fatores[$fator]], $fator), PASSWORD_DEFAULT),
            ];
            audit($db, (int)$user['id'], $user['login'], $user['nome'], 'LOGIN', 'SENHA_OK_2FA_PENDENTE', 'SENHA');
            json_response(['ok'=>true,'requires_2fa'=>true,'question'=>pergunta_2fa($fator),'factor'=>$fator]);

        case 'challenge_2fa':
            require_method('GET');
            $pending = $_SESSION['pending_2fa'] ?? null;
            if (!$pending || time() > $pending['expires']) {
                unset($_SESSION['pending_2fa']);
                json_response(['ok'=>false,'message'=>'Desafio expirado. Faça o login novamente.','restart_login'=>true],401);
            }
            json_response(['ok'=>true,'question'=>pergunta_2fa($pending['fator']),'factor'=>$pending['fator'],'attempts_remaining'=>3-$pending['tentativas']]);

        case 'cancel_2fa':
            require_method('POST');
            unset($_SESSION['pending_2fa']);
            json_response(['ok'=>true]);

        case 'verify_2fa':
            require_method('POST');
            $d = input_json();
            $pending = $_SESSION['pending_2fa'] ?? null;
            if (!$pending || time() > $pending['expires']) {
                unset($_SESSION['pending_2fa']);
                json_response(['ok'=>false,'message'=>'Desafio expirado. Faça o login novamente.','restart_login'=>true],401);
            }
            $answer = trim((string)($d['answer'] ?? ''));
            if ($answer === '') json_response(['ok'=>false,'message'=>'Preencha a resposta.'],422);
            if (!password_verify(normalizar_resposta($answer, $pending['fator']), $pending['answer_hash'])) {
                $_SESSION['pending_2fa']['tentativas']++;
                audit($db, $pending['user_id'], $pending['login'], $pending['nome'], '2FA', 'FALHA', $pending['fator']);
                $remaining = 3 - $_SESSION['pending_2fa']['tentativas'];
                if ($remaining <= 0) {
                    unset($_SESSION['pending_2fa']);
                    json_response(['ok'=>false,'message'=>'3 tentativas sem sucesso! Favor realizar Login novamente.','restart_login'=>true],401);
                }
                json_response(['ok'=>false,'message'=>"Resposta incorreta. Restam {$remaining} tentativa(s).",'attempts_remaining'=>$remaining],401);
            }
            $stmt = $db->prepare('SELECT * FROM usuarios WHERE id = ? AND ativo = 1 LIMIT 1');
            $stmt->execute([$pending['user_id']]);
            $user = $stmt->fetch();
            if (!$user) {
                unset($_SESSION['pending_2fa']);
                json_response(['ok'=>false,'message'=>'Usuário não encontrado.','restart_login'=>true],404);
            }
            session_regenerate_id(true);
            $_SESSION['user'] = clean_user($user);
            unset($_SESSION['pending_2fa']);
            audit($db, (int)$user['id'], $user['login'], $user['nome'], '2FA', 'SUCESSO', $pending['fator']);
            json_response(['ok'=>true,'message'=>'Autenticação concluída.','user'=>clean_user($user)]);

        case 'logout':
            require_method('POST');
            $user = current_user();
            if ($user) audit($db, $user['id'], $user['login'], $user['nome'], 'LOGOUT', 'SUCESSO', 'SESSAO');
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time()-42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
            json_response(['ok'=>true]);

        case 'profile':
            $user = require_login();
            $stmt = $db->prepare('SELECT * FROM usuarios WHERE id = ? LIMIT 1');
            $stmt->execute([(int)$user['id']]);
            $row = $stmt->fetch();
            json_response(['ok'=>true,'user'=>$row ? clean_user($row) : null]);

        case 'update_profile':
            require_method('POST');
            $user = require_login();
            if (($user['perfil'] ?? '') !== 'COMUM') json_response(['ok'=>false,'message'=>'Somente o Usuário Comum pode alterar os próprios dados por esta tela.'],403);
            $d = input_json();
            $nome = trim((string)($d['nome'] ?? ''));
            $email = trim((string)($d['email'] ?? ''));
            $celular = telefone_nacional((string)($d['celular'] ?? ''));
            $cidade = trim((string)($d['cidade'] ?? ''));
            $estado = strtoupper(trim((string)($d['estado'] ?? '')));
            if (!nome_valido($nome)) json_response(['ok'=>false,'message'=>'Nome deve ter entre 15 e 80 caracteres, somente letras e espaços.'],422);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_response(['ok'=>false,'message'=>'E-mail inválido.'],422);
            if (!in_array(strlen($celular), [10,11], true)) json_response(['ok'=>false,'message'=>'Celular inválido.'],422);
            if ($cidade === '' || strlen($estado) !== 2) json_response(['ok'=>false,'message'=>'Cidade e estado são obrigatórios.'],422);
            $up = $db->prepare('UPDATE usuarios SET nome = ?, email = ?, celular = ?, cidade = ?, estado = ? WHERE id = ?');
            $up->execute([$nome,$email,$celular,$cidade,$estado,(int)$user['id']]);
            $stmt = $db->prepare('SELECT * FROM usuarios WHERE id = ? LIMIT 1');
            $stmt->execute([(int)$user['id']]);
            $updated = $stmt->fetch();
            if (!$updated) json_response(['ok'=>false,'message'=>'Usuário não encontrado.'],404);
            $_SESSION['user'] = clean_user($updated);
            audit($db,(int)$updated['id'],$updated['login'],$updated['nome'],'ALTERACAO_DADOS','SUCESSO','SESSAO');
            json_response(['ok'=>true,'message'=>'Dados alterados com sucesso.','user'=>clean_user($updated)]);

        case 'change_password':
            require_method('POST');
            $user = require_login();
            if (($user['perfil'] ?? '') !== 'COMUM') json_response(['ok'=>false,'message'=>'Esta função é destinada ao Usuário Comum.'],403);
            $d = input_json();
            $atual = (string)($d['senhaAtual'] ?? '');
            $nova = (string)($d['novaSenha'] ?? '');
            $confirmar = (string)($d['confirmarSenha'] ?? '');
            if (!letras($nova, 8) || $nova !== $confirmar) json_response(['ok'=>false,'message'=>'A nova senha deve ter exatamente 8 letras e coincidir com a confirmação.'],422);
            $stmt = $db->prepare('SELECT senha_hash FROM usuarios WHERE id = ?');
            $stmt->execute([(int)$user['id']]);
            $row = $stmt->fetch();
            if (!$row || !password_verify($atual, $row['senha_hash'])) json_response(['ok'=>false,'message'=>'Senha atual incorreta.'],401);
            $up = $db->prepare('UPDATE usuarios SET senha_hash = ? WHERE id = ?');
            $up->execute([password_hash($nova, PASSWORD_DEFAULT), (int)$user['id']]);
            audit($db, $user['id'], $user['login'], $user['nome'], 'ALTERACAO_SENHA', 'SUCESSO', 'SENHA');
            json_response(['ok'=>true,'message'=>'Senha alterada com sucesso.']);

        case 'users':
            $master = require_master();
            $q = trim((string)($_GET['q'] ?? ''));
            $stmt = $db->prepare('SELECT id,nome,login,email,cpf,celular,cidade,estado,perfil,ativo FROM usuarios WHERE perfil=\'COMUM\' AND nome LIKE ? ORDER BY nome ASC');
            $like = '%' . $q . '%';
            $stmt->execute([$like]);
            json_response(['ok'=>true,'users'=>$stmt->fetchAll()]);

        case 'delete_user':
            $master = require_master();
            require_method('POST');
            $d = input_json();
            $id = (int)($d['id'] ?? 0);
            if ($id <= 0 || $id === (int)$master['id']) json_response(['ok'=>false,'message'=>'Usuário inválido ou operação não permitida.'],422);
            $stmt = $db->prepare('SELECT id,nome,login,perfil FROM usuarios WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $target = $stmt->fetch();
            if (!$target) json_response(['ok'=>false,'message'=>'Usuário não encontrado.'],404);
            if ($target['perfil'] === 'MASTER') json_response(['ok'=>false,'message'=>'O usuário Master não pode ser excluído por esta tela.'],403);
            $del = $db->prepare('DELETE FROM usuarios WHERE id = ?');
            $del->execute([$id]);
            audit($db, $master['id'], $master['login'], $master['nome'], 'EXCLUSAO_USUARIO', 'SUCESSO', 'SESSAO');
            json_response(['ok'=>true,'message'=>'Usuário excluído com sucesso.']);

        case 'logs':
            $master = require_master();
            $q = trim((string)($_GET['q'] ?? ''));
            $stmt = $db->prepare('SELECT l.data_hora,l.nome,l.login,l.evento,l.fator_autenticacao,l.resultado,l.ip FROM logs_autenticacao l LEFT JOIN usuarios u ON u.id=l.usuario_id WHERE l.evento IN (\'LOGIN\',\'2FA\') AND (u.perfil=\'COMUM\' OR u.id IS NULL) AND (l.nome LIKE ? OR l.login LIKE ? OR l.cpf LIKE ?) ORDER BY l.data_hora DESC,l.id DESC LIMIT 500');
            $like = '%' . $q . '%';
            $stmt->execute([$like,$like,$like]);
            json_response(['ok'=>true,'logs'=>$stmt->fetchAll()]);

        default:
            json_response(['ok'=>false,'message'=>'Ação não encontrada.'],404);
    }
} catch (PDOException $e) {
    json_response(['ok'=>false,'message'=>'Erro de banco de dados. Verifique se o MySQL está ligado e o banco boardsurf foi importado.','detail'=>$e->getMessage()],500);
} catch (Throwable $e) {
    json_response(['ok'=>false,'message'=>'Erro interno do servidor.','detail'=>$e->getMessage()],500);
}
