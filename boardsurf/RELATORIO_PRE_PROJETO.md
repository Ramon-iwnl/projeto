# BoardSurf — pré-projeto

O documento de avaliação concentra o pré-projeto no Front-End (telas, identidade visual,
críticas dos dados, responsividade e qualidade técnica) e no Modelo ER. Esta versão
mantém o PHP necessário para que as telas e validações continuem funcionando.

| Critério | Implementação |
| --- | --- |
| Página principal e catálogo | Descrições, produtos e filtros existentes preservados. |
| Cadastro | Todos os campos, rótulos, Enviar/Limpar, senha visível opcional e feedback por campo. |
| Validações | Nome alfabético com espaços, 15–80 caracteres; login com 6 letras; senha com 8 letras; confirmação; CPF; telefones; e-mail; data; CEP/endereço e UF. Validações também no PHP. |
| Login | Login, senha, Entrar, Limpar e link para cadastro. |
| 2FA | Pergunta sorteada sobre mãe, nascimento ou CEP; resposta, envio, três tentativas e retorno ao login. A resposta esperada não é enviada ao navegador. |
| Erros | Mensagens por campo, modal de erro e tela erro.html com orientações. |
| Consulta de usuários | Tela Master, busca por substring do nome de COMUM e exclusão com confirmação. |
| Alteração de senha | Tela exclusiva de COMUM; validação, confirmação e armazenamento com hash. |
| Logs | Tela Master, filtro por nome/CPF (vazio mostra todos), registros em ordem recente e fator identificado. |
| Sessão/menu | Login do usuário, menu e logout em todas as telas HTML após autenticação, inclusive no celular. |
| Modelo ER | Imagens PNG/SVG com todos os atributos das três tabelas, PK/FK/UK e cardinalidades; tela Modelo do BD. |
| Responsividade | Cinco larguras testadas no Chromium; sem cortes de página nem sobreposição entre os blocos do cabeçalho. Tabelas/DER usam rolagem interna para manter legibilidade. |
| Acessibilidade | Contraste e ajuste de fonte preservados; rótulos dos campos e dos botões. |

## Evidências e limites

- Sintaxe de PHP e JavaScript validada.
- API testada com PHP 8.4.26 e MariaDB 11.8.6 em banco temporário.
- Cadastro, autenticação, desafio por perguntas, senha, permissões, consulta, logs e exclusão exercitados com banco real.
- 65 combinações de página/largura verificadas (13 páginas × 5 larguras).
- Fluxo de cadastro/login/2FA, fonte ampliada, contraste, carrinho e logout testados pelo navegador.
- Não foi testado no XAMPP do aluno nem em todos os navegadores/aparelhos. Use TESTES_XAMPP.txt antes de apresentar.
- O carrinho não cria pedidos no banco nem processa pagamentos; o enunciado não exige checkout.
- PDF de usuários é desafio extra e está fora desta entrega.

## Preparação da entrega

1. Rode o roteiro de demonstração no XAMPP.
2. Confirme número do grupo, nome dos integrantes e convenção de nome do arquivo com o professor.
   O documento tem uma referência a “2024-1”, embora o arquivo enviado seja de 2026-2.
3. Inclua o Front-End, PHP, banco.sql e DER-BoardSurf.png (ou a imagem SVG).
4. Não inclua um dump com dados pessoais reais, senhas personalizadas ou credenciais locais.
5. Esta comparação com o enunciado não substitui a avaliação do professor.
