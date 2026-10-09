function mostrarMensagem(texto) {
  const msg = document.getElementById("mensagem");
  if (!msg) return;
  msg.innerText = texto; msg.style.display = "block";
  clearTimeout(window.__msgTimer); window.__msgTimer = setTimeout(() => msg.style.display = "none", 3500);
}
function mostrarErroCampo(id, mensagem) {
  const campo = document.getElementById(id);
  if (!campo) return;
  campo.classList.add('input-error');
  campo.setAttribute('aria-invalid', 'true');
  const erroId = id + '-erro';
  let erro = document.getElementById(erroId);
  if (!erro) {
    erro = document.createElement('span');
    erro.id = erroId;
    erro.className = 'error-message';
    erro.setAttribute('role', 'alert');
    (campo.closest('.input-icon,.cadastro-senha') || campo).after(erro);
  }
  erro.textContent = mensagem;
  campo.setAttribute('aria-describedby', erroId);
}
function limparErros(form) {
  if (!form) return;
  form.querySelectorAll('.error-message').forEach(e => e.remove());
  form.querySelectorAll('.input-error').forEach(e => {
    e.classList.remove('input-error');
    e.removeAttribute('aria-invalid');
    e.removeAttribute('aria-describedby');
  });
}
function togglePassword(id, btn) {
  const input = document.getElementById(id);
  if (!input) return;
  input.type = input.type === 'password' ? 'text' : 'password';
  const visivel = input.type === 'text';
  btn.innerHTML = '<i class="fa-solid ' + (visivel ? 'fa-eye-slash' : 'fa-eye') + '" aria-hidden="true"></i>';
  btn.setAttribute('aria-label', (visivel ? 'Ocultar' : 'Mostrar') + ' senha');
  btn.setAttribute('aria-pressed', String(visivel));
}
function mostrarErro(mensagem) {
  const modal = document.getElementById('erro-modal');
  if (!modal) { mostrarMensagem(mensagem); return; }
  document.getElementById('erro-texto').textContent = mensagem;
  if (!modal.open) modal.showModal();
}
function telefoneNacional(valor) {
  let digitos = valor.replace(/\D/g, '');
  if (digitos.startsWith('55') && (valor.includes('+') || digitos.length > 11)) digitos = digitos.slice(2);
  return digitos;
}
function somenteLetras(valor, tamanho) {
  return Array.from(valor).length === tamanho && /^\p{L}+$/u.test(valor);
}
function nomeValido(valor) {
  return Array.from(valor).length >= 15 && Array.from(valor).length <= 80 && /^\p{L}+(?: +\p{L}+)*$/u.test(valor);
}
function aumentarFonte(){document.documentElement.style.fontSize=Math.min(parseFloat(getComputedStyle(document.documentElement).fontSize)+2,24)+'px';}
function diminuirFonte(){document.documentElement.style.fontSize=Math.max(parseFloat(getComputedStyle(document.documentElement).fontSize)-2,12)+'px';}
function toggleDarkMode(){document.body.classList.toggle('dark');localStorage.setItem('theme',document.body.classList.contains('dark')?'dark':'light');}
function validarCPF(cpf){cpf=cpf.replace(/\D/g,'');if(cpf.length!==11||/^(\d)\1+$/.test(cpf))return false;let s=0;for(let i=0;i<9;i++)s+=+cpf[i]*(10-i);let r=(s*10)%11;if(r===10||r===11)r=0;if(r!==+cpf[9])return false;s=0;for(let i=0;i<10;i++)s+=+cpf[i]*(11-i);r=(s*10)%11;if(r===10||r===11)r=0;return r===+cpf[10];}
function configurarMascaras() {
  const cpf = document.getElementById('cpf');
  if (cpf) cpf.addEventListener('input', e => {
    let v = e.target.value.replace(/\D/g, '').slice(0,11);
    e.target.value = v.replace(/^(\d{3})(\d)/, '$1.$2').replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3').replace(/^(\d{3})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3-$4');
  });
  for (const id of ['celular', 'telefoneFixo']) {
    const campo = document.getElementById(id);
    if (campo) campo.addEventListener('input', e => {
      const v = telefoneNacional(e.target.value).slice(0, id === 'celular' ? 11 : 10);
      e.target.value = v ? '(+55)' + v.slice(0,2) + (v.length > 2 ? '-' + v.slice(2) : '') : '';
    });
  }
  const cep = document.getElementById('cep');
  if (cep) {
    cep.addEventListener('input', e => { e.target.value = e.target.value.replace(/\D/g,'').slice(0,8).replace(/^(\d{5})(\d)/,'$1-$2'); });
    cep.addEventListener('blur', () => buscarViaCEP(cep.value.replace(/\D/g,'')));
  }
}
async function buscarViaCEP(cep) {
  if (!/^\d{8}$/.test(cep) || cep === '00000000') return;
  try {
    const res = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
    if (!res.ok) throw new Error('Consulta indisponível');
    const dados = await res.json();
    if (document.getElementById('cep').value.replace(/\D/g,'') !== cep) return;
    if (dados.erro) { mostrarErroCampo('cep', 'CEP não encontrado. Confira o número.'); return; }
    limparErroCampo('cep');
    for (const [id, valor] of [['logradouro',dados.logradouro],['bairro',dados.bairro],['cidade',dados.localidade],['estado',dados.uf]]) {
      const campo = document.getElementById(id);
      if (campo) campo.value = valor || '';
    }
  } catch (erro) {
    mostrarMensagem('Consulta de CEP indisponível. Preencha o endereço manualmente.');
  }
}
function limparErroCampo(id) {
  const campo = document.getElementById(id), erro = document.getElementById(id + '-erro');
  if (erro) erro.remove();
  if (campo) { campo.classList.remove('input-error'); campo.removeAttribute('aria-invalid'); campo.removeAttribute('aria-describedby'); }
}
async function api(action,options={}){
  if(window.location.protocol==='file:') throw new Error('O projeto está sendo aberto como arquivo. Abra pelo XAMPP em http://localhost/boardsurf/ para o PHP e o banco funcionarem.');
  let actionName=action, query='';
  if(action.includes('&')){const parts=action.split('&'); actionName=parts.shift(); query='&'+parts.join('&');}
  let res;
  try{
    res=await fetch(`api.php?action=${encodeURIComponent(actionName)}${query}`,{credentials:'same-origin',headers:{'Content-Type':'application/json',...(options.headers||{})},...options});
  }catch(e){throw new Error('Não foi possível acessar o servidor PHP. Verifique se o Apache do XAMPP está iniciado e se você abriu o projeto por http://localhost/boardsurf/.');}
  const responseText=await res.text();
  let data={};
  try{
    data=responseText ? JSON.parse(responseText) : {};
  }catch(e){
    console.error('Resposta bruta do servidor:', responseText);
    const detalhe=responseText.replace(/<[^>]*>/g,' ').replace(/\s+/g,' ').trim().slice(0,500);
    throw new Error(detalhe || `O servidor respondeu de forma inválida (HTTP ${res.status}). Abra diagnostico.php para verificar o PHP/MySQL.`);
  }
  if(!res.ok||data.ok===false){const erro=new Error(data.message||`Erro HTTP ${res.status}.`);erro.restartLogin=!!data.restart_login;erro.status=res.status;throw erro;}
  return data;
}


function inicializarCadastro() {
  const form = document.getElementById('formCadastro');
  if (!form) return;
  form.addEventListener('submit', async e => {
    e.preventDefault(); limparErros(form);
    const get = id => document.getElementById(id).value.trim();
    const data = {
      nome:get('nome'), dataNascimento:get('dataNascimento'), sexo:get('sexo'), materno:get('materno'),
      cpf:get('cpf').replace(/\D/g,''), email:get('email'), celular:telefoneNacional(get('celular')),
      telefoneFixo:telefoneNacional(get('telefoneFixo')), cep:get('cep').replace(/\D/g,''),
      logradouro:get('logradouro'), numero:get('numero'), bairro:get('bairro'), cidade:get('cidade'),
      estado:get('estado').toUpperCase(), login:get('loginUsuario'),
      senha:document.getElementById('senha').value, confirmarSenha:document.getElementById('confirmarSenha').value
    };
    let erro = false;
    const invalidar = (id, mensagem) => { mostrarErroCampo(id, mensagem); erro = true; };
    const ids = {login:'loginUsuario',telefoneFixo:'telefoneFixo'};
    Object.entries(data).forEach(([campo,valor]) => { if (!String(valor).trim()) invalidar(ids[campo] || campo, 'Preencha este campo.'); });
    if (!nomeValido(data.nome)) invalidar('nome','Use de 15 a 80 caracteres, somente letras e espaços.');
    if (!/^\p{L}+(?: +\p{L}+)*$/u.test(data.materno) || data.materno.length > 80) invalidar('materno','Use letras e espaços, até 80 caracteres.');
    const nascimento = new Date(data.dataNascimento + 'T00:00:00');
    if (!/^\d{4}-\d{2}-\d{2}$/.test(data.dataNascimento) || isNaN(nascimento) || nascimento > new Date()) invalidar('dataNascimento','Informe uma data de nascimento válida.');
    if (!['Masculino','Feminino','Outro'].includes(data.sexo)) invalidar('sexo','Selecione uma opção.');
    if (!validarCPF(data.cpf)) invalidar('cpf','CPF inválido.');
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) invalidar('email','Informe um e-mail válido.');
    if (!/^\d{10,11}$/.test(data.celular)) invalidar('celular','Informe DDD e número do celular, com +55.');
    if (!/^\d{10}$/.test(data.telefoneFixo)) invalidar('telefoneFixo','Informe DDD e os 8 números do telefone fixo, com +55.');
    if (!/^\d{8}$/.test(data.cep) || data.cep === '00000000') invalidar('cep','CEP inválido.');
    if (!/^[A-Z]{2}$/.test(data.estado)) invalidar('estado','Use a UF com duas letras, por exemplo SP.');
    if (!somenteLetras(data.login,6)) invalidar('loginUsuario','Login deve ter exatamente 6 letras.');
    if (!somenteLetras(data.senha,8)) invalidar('senha','Senha deve ter exatamente 8 letras.');
    if (data.senha !== data.confirmarSenha) invalidar('confirmarSenha','As senhas não coincidem.');
    if (erro) { form.querySelector('.input-error')?.focus(); return; }
    const enviar = form.querySelector('[type="submit"]'); enviar.disabled = true;
    try {
      await api('register',{method:'POST',body:JSON.stringify(data)});
      mostrarMensagem('Cadastro realizado com sucesso! Redirecionando para o login...');
      setTimeout(() => location.replace('login.html'),1200);
    } catch (x) { mostrarErro(x.message); enviar.disabled = false; }
  });
}

let pendingLogin2FA = false;
function configurarPergunta2FA(resposta) {
  const input = document.getElementById('resposta2fa');
  document.getElementById('pergunta2fa').textContent = resposta.question;
  input.value = '';
  input.type = resposta.factor === 'DATA_NASCIMENTO' ? 'date' : 'text';
  input.inputMode = resposta.factor === 'CEP' ? 'numeric' : 'text';
  input.maxLength = resposta.factor === 'CEP' ? 9 : 80;
  input.placeholder = resposta.factor === 'CEP' ? '00000-000' : 'Digite sua resposta';
  limparErros(document.getElementById('form2fa'));
}
function inicializarLogin() {
  const form = document.getElementById('formLogin');
  if (form) form.addEventListener('submit', async e => {
    e.preventDefault(); limparErros(form);
    const login = document.getElementById('campoUserLogin').value.trim();
    const senha = document.getElementById('senhaLogin').value;
    if (!login || !senha) {
      if (!login) mostrarErroCampo('campoUserLogin','Informe o login.');
      if (!senha) mostrarErroCampo('senhaLogin','Informe a senha.');
      return;
    }
    const enviar = form.querySelector('[type="submit"]'); enviar.disabled = true;
    try {
      const r = await api('login',{method:'POST',body:JSON.stringify({login,senha})});
      pendingLogin2FA = true;
      configurarPergunta2FA(r);
      document.getElementById('modal-2fa').style.display = 'flex';
      document.getElementById('resposta2fa').focus();
    } catch (x) { mostrarErro(x.message); }
    finally { enviar.disabled = false; }
  });
  const f2 = document.getElementById('form2fa');
  if (f2) f2.addEventListener('submit', async e => {
    e.preventDefault(); limparErros(f2);
    const answer = document.getElementById('resposta2fa').value.trim();
    if (!answer) { mostrarErroCampo('resposta2fa','Preencha a resposta.'); return; }
    const enviar = f2.querySelector('[type="submit"]'); enviar.disabled = true;
    try {
      const r = await api('verify_2fa',{method:'POST',body:JSON.stringify({answer})});
      pendingLogin2FA = false; localStorage.removeItem('usuarioLogado');
      mostrarMensagem(r.message); setTimeout(() => location.href='index.html',700);
    } catch (x) {
      if (x.restartLogin) {
        pendingLogin2FA = false;
        const modal = document.getElementById('modal-2fa'); if (modal) modal.style.display='none';
        sessionStorage.setItem('erroLogin', x.message);
        location.replace('login.html');
      } else mostrarErroCampo('resposta2fa',x.message);
    } finally { enviar.disabled = false; }
  });
  if (document.body.dataset.page === '2fa') carregarDesafio2FA();
  const erro = sessionStorage.getItem('erroLogin');
  if (form && erro) { sessionStorage.removeItem('erroLogin'); mostrarErro(erro); }
}
async function carregarDesafio2FA() {
  try { configurarPergunta2FA(await api('challenge_2fa')); }
  catch (erro) { sessionStorage.setItem('erroLogin',erro.message); location.replace('login.html'); }
}
async function fechar2FA() {
  const modal = document.getElementById('modal-2fa'); if (modal) modal.style.display='none';
  pendingLogin2FA = false;
  try { await api('cancel_2fa',{method:'POST'}); } catch (erro) { mostrarErro(erro.message); }
}
async function deslogarUsuario() {
  try { await api('logout',{method:'POST'}); location.href='index.html'; }
  catch (erro) { mostrarErro(erro.message); }
}
async function carregarSessao() {
  try {
    const r = await api('me');
    const nome = document.getElementById('usuario-logado-nome');
    const visitante = document.getElementById('visitante-botoes');
    const perfil = document.getElementById('btn-perfil'), sair = document.getElementById('btn-sair');
    if (nome) { nome.hidden = !r.authenticated; nome.style.display = r.authenticated ? 'flex' : 'none'; if (r.authenticated) nome.querySelector('span').textContent = r.user.login + ' (' + r.user.perfil + ')'; }
    if (visitante) visitante.style.display = r.authenticated ? 'none' : 'flex';
    if (perfil) perfil.style.display = r.authenticated ? 'inline-flex' : 'none';
    if (sair) sair.style.display = r.authenticated ? 'inline-flex' : 'none';
    document.querySelectorAll('.master-only').forEach(e => e.style.display = r.authenticated && r.user.perfil === 'MASTER' ? 'inline-flex' : 'none');
    document.querySelectorAll('.common-only').forEach(e => e.style.display = r.authenticated && r.user.perfil === 'COMUM' ? 'inline-flex' : 'none');
    const editar = document.getElementById('btn-editar-perfil'); if (editar) editar.hidden = !r.authenticated || r.user.perfil !== 'COMUM';
    if (perfil && !perfil.dataset.bound) { perfil.dataset.bound='1'; perfil.addEventListener('click',abrirModalPerfil); }
    const role = document.body.dataset.requiredRole;
    if (role && (!r.authenticated || r.user.perfil !== role)) {
      sessionStorage.setItem('erroLogin','Esta página exige uma conta ' + role + ' autenticada.'); location.replace('login.html'); return;
    }
    if (document.getElementById('tabelaUsuarios')) carregarUsuarios();
    if (document.getElementById('tabelaLogs')) carregarLogs();
  } catch (erro) { mostrarErro(erro.message); }
}
async function abrirModalPerfil(){
  try{
    const r=await api('profile'),u=r.user;
    const map={"perf-nome":u.nome,"perf-email":u.email,"perf-celular":u.celular,"perf-cidade":u.cidade,"perf-estado":u.estado};
    Object.entries(map).forEach(([id,v])=>{const el=document.getElementById(id);if(el)el.value=v||'';});
    const modal=document.getElementById('perfil-modal');if(modal)modal.style.display='flex';
    bloquearEdicaoPerfil();
  }catch(e){mostrarErro(e.message);}
}
function fecharModalPerfil(){const m=document.getElementById('perfil-modal');if(m)m.style.display='none';}
function bloquearEdicaoPerfil(){
  ['perf-nome','perf-email','perf-celular','perf-cidade','perf-estado'].forEach(id=>{const el=document.getElementById(id);if(el)el.disabled=true;});
  const editar=document.getElementById('btn-editar-perfil'),salvar=document.getElementById('btn-salvar-perfil');
  if(editar)editar.style.display='inline-flex'; if(salvar)salvar.style.display='none';
}
function ativarEdicaoPerfil(){
  ['perf-nome','perf-email','perf-celular','perf-cidade','perf-estado'].forEach(id=>{const el=document.getElementById(id);if(el)el.disabled=false;});
  const editar=document.getElementById('btn-editar-perfil'),salvar=document.getElementById('btn-salvar-perfil');
  if(editar)editar.style.display='none'; if(salvar)salvar.style.display='inline-flex';
}
async function salvarPerfil(e){
  e.preventDefault();
  const data={
    nome:document.getElementById('perf-nome')?.value.trim()||'',
    email:document.getElementById('perf-email')?.value.trim()||'',
    celular:telefoneNacional(document.getElementById('perf-celular')?.value||''),
    cidade:document.getElementById('perf-cidade')?.value.trim()||'',
    estado:document.getElementById('perf-estado')?.value.trim()||''
  };
  try{
    const r=await api('update_profile',{method:'POST',body:JSON.stringify(data)});
    mostrarMensagem(r.message); bloquearEdicaoPerfil(); await carregarSessao();
  }catch(x){mostrarErro(x.message);}
}

function inicializarAlterarSenha() {
  const form = document.getElementById('formAlterarSenha'); if (!form) return;
  form.addEventListener('submit', async e => {
    e.preventDefault(); limparErros(form);
    const senhaAtual = document.getElementById('senhaAtual').value;
    const novaSenha = document.getElementById('novaSenha').value;
    const confirmarSenha = document.getElementById('confirmarNovaSenha').value;
    if (!senhaAtual) { mostrarErroCampo('senhaAtual','Informe a senha atual.'); return; }
    if (!somenteLetras(novaSenha,8)) { mostrarErroCampo('novaSenha','Use exatamente 8 letras.'); return; }
    if (novaSenha !== confirmarSenha) { mostrarErroCampo('confirmarNovaSenha','As senhas não coincidem.'); return; }
    try {
      const r = await api('change_password',{method:'POST',body:JSON.stringify({senhaAtual,novaSenha,confirmarSenha})});
      mostrarMensagem(r.message); form.reset();
    } catch (erro) { mostrarErro(erro.message); }
  });
}
function inserirCelula(linha, texto) {
  const celula=document.createElement('td'); celula.textContent=texto ?? '-'; linha.appendChild(celula); return celula;
}
async function carregarUsuarios() {
  try {
    const q=document.getElementById('buscaUsuario')?.value || '';
    const r=await api('users&q='+encodeURIComponent(q));
    const tabela=document.getElementById('tabelaUsuarios'); if (!tabela) return;
    tabela.replaceChildren();
    for (const user of r.users) {
      const linha=document.createElement('tr');
      for (const campo of ['nome','login','email','cpf','perfil']) inserirCelula(linha,user[campo]);
      const acoes=inserirCelula(linha,''); const excluir=document.createElement('button');
      excluir.type='button'; excluir.className='btn-delete'; excluir.textContent='Excluir';
      excluir.addEventListener('click',()=>excluirUsuario(Number(user.id),user.nome)); acoes.appendChild(excluir); tabela.appendChild(linha);
    }
    if (!r.users.length) { const linha=document.createElement('tr'); inserirCelula(linha,'Nenhum usuário comum encontrado.').colSpan=6; tabela.appendChild(linha); }
  } catch (erro) { mostrarErro(erro.message); }
}
async function excluirUsuario(id,nome) {
  if (!await confirmarAcao(`Confirma a exclusão de ${nome}?`)) return;
  try { const r=await api('delete_user',{method:'POST',body:JSON.stringify({id})}); mostrarMensagem(r.message); carregarUsuarios(); }
  catch (erro) { mostrarErro(erro.message); }
}
async function carregarLogs() {
  try {
    const q=document.getElementById('buscaLog')?.value || '';
    const r=await api('logs&q='+encodeURIComponent(q));
    const tabela=document.getElementById('tabelaLogs'); if (!tabela) return;
    tabela.replaceChildren();
    for (const log of r.logs) {
      const linha=document.createElement('tr');
      for (const campo of ['data_hora','nome','login','evento','fator_autenticacao','resultado','ip']) inserirCelula(linha,log[campo]);
      tabela.appendChild(linha);
    }
    if (!r.logs.length) { const linha=document.createElement('tr'); inserirCelula(linha,'Nenhum log encontrado.').colSpan=7; tabela.appendChild(linha); }
  } catch (erro) { mostrarErro(erro.message); }
}

let carrinho = JSON.parse(localStorage.getItem('carrinho') || '[]');

function atualizarContador() {
  const contador = document.getElementById('contador-carrinho');
  if (contador) contador.textContent = carrinho.length;
}

function atualizarCarrinho() {
  atualizarContador();

  const lista = document.getElementById('lista-carrinho');
  const total = document.getElementById('total-carrinho');

  if (lista) {
    lista.replaceChildren();

    if (carrinho.length === 0) {
      const vazio = document.createElement('li');
      vazio.textContent = 'Seu carrinho está vazio.';
      lista.appendChild(vazio);
    }

    carrinho.forEach((produto, indice) => {
      const item = document.createElement('li');
      item.className = 'item-carrinho';

      const descricao = document.createElement('span');
      descricao.textContent =
        produto.nome + ' — ' +
        Number(produto.preco).toLocaleString('pt-BR', {
          style: 'currency',
          currency: 'BRL'
        });

      const remover = document.createElement('button');
      remover.type = 'button';
      remover.className = 'btn-remover';
      remover.textContent = 'Remover';
      remover.setAttribute('aria-label', 'Remover ' + produto.nome);
      remover.addEventListener('click', () => removerDoCarrinho(indice));

      item.append(descricao, remover);
      lista.appendChild(item);
    });
  }

  if (total) {
    total.textContent = carrinho
      .reduce((soma, produto) => soma + Number(produto.preco), 0)
      .toFixed(2);
  }
}

function adicionarAoCarrinho(nome, preco) {
  if (!nome || !Number.isFinite(Number(preco))) return;

  carrinho.push({ nome, preco: Number(preco) });
  localStorage.setItem('carrinho', JSON.stringify(carrinho));
  atualizarCarrinho();
  mostrarMensagem('Produto adicionado ao carrinho.');
}

function removerDoCarrinho(indice) {
  if (!Number.isInteger(indice) || indice < 0 || indice >= carrinho.length) {
    return;
  }

  carrinho.splice(indice, 1);
  localStorage.setItem('carrinho', JSON.stringify(carrinho));
  atualizarCarrinho();
}

function abrirCarrinho() {
  atualizarCarrinho();

  const painel = document.getElementById('carrinho-lateral');
  const overlay = document.getElementById('carrinho-overlay');

  if (painel) painel.classList.add('open');
  if (overlay) overlay.style.display = 'block';
}

function fecharCarrinho() {
  const painel = document.getElementById('carrinho-lateral');
  const overlay = document.getElementById('carrinho-overlay');

  if (painel) painel.classList.remove('open');
  if (overlay) overlay.style.display = 'none';
}

function inicializarCarrinho() {
  // Cria o painel nas páginas de categorias que ainda não o possuem.
  if (
    document.querySelector('.carrinho-link') &&
    !document.getElementById('carrinho-lateral')
  ) {
    const container = document.createElement('div');

    container.innerHTML = `
      <div class="carrinho-overlay" id="carrinho-overlay"></div>
      <div class="carrinho-lateral" id="carrinho-lateral">
        <div class="header-sacola">
          <h3>MINHA SACOLA</h3>
          <button type="button" id="btn-fechar-carrinho"
                  aria-label="Fechar carrinho">X</button>
        </div>
        <ul id="lista-carrinho"></ul>
        <div class="rodape-sacola">
          <p>Subtotal: R$ <span id="total-carrinho">0.00</span></p>
          <button type="button" id="btn-continuar-comprando">
            CONTINUAR COMPRANDO
          </button>
        </div>
      </div>
    `;

    document.body.append(...container.childNodes);
  }

  const fechar = document.getElementById('btn-fechar-carrinho');
  const overlay = document.getElementById('carrinho-overlay');
  const continuar = document.getElementById('btn-continuar-comprando');

  if (fechar) fechar.addEventListener('click', fecharCarrinho);

  if (overlay && !overlay.hasAttribute('onclick')) {
    overlay.addEventListener('click', fecharCarrinho);
  }

  if (continuar) continuar.addEventListener('click', fecharCarrinho);

  document.addEventListener('keydown', (evento) => {
    if (evento.key === 'Escape') fecharCarrinho();
  });

  atualizarCarrinho();
}
function prepararMascarasCarrinho(){document.querySelectorAll('.btn-adicionar').forEach(b=>{if(!b.dataset.bound){b.dataset.bound='1';b.addEventListener('click',e=>{const n=b.dataset.nome||b.getAttribute('data-nome');const p=parseFloat(b.dataset.preco||b.getAttribute('data-preco'));if(n)addicionar(n,p);});}});}
function addicionar(n,p){adicionarAoCarrinho(n,p);}
window.addEventListener('click',e=>{const m=document.getElementById('perfil-modal');if(m&&e.target===m)fecharModalPerfil();});
window.addEventListener('DOMContentLoaded',()=>{
  if(localStorage.getItem('theme')==='dark')document.body.classList.add('dark');
  configurarMascaras(); inicializarCarrinho(); carregarSessao(); carregarProdutos();
  inicializarCadastro(); inicializarLogin(); inicializarAlterarSenha();
  const perfilForm=document.getElementById('form-perfil-edit');
  if(perfilForm) perfilForm.addEventListener('submit',salvarPerfil);
});

async function carregarProdutos(){
 const grade=document.querySelector('.grade-produtos[data-categoria]');if(!grade)return;
 try{
  const categoria=grade.dataset.categoria||'';
  const resposta=await api('products&categoria='+encodeURIComponent(categoria));
  grade.replaceChildren();
  if(!resposta.products.length){grade.textContent='Nenhum produto cadastrado.';return;}
  for(const p of resposta.products){
   const card=document.createElement('div');card.className='card-produto';
   const img=document.createElement('img');img.src=p.imagem;img.alt=p.nome;img.loading='lazy';card.appendChild(img);
   const avaliacao=document.createElement('div');avaliacao.className='avaliacao';avaliacao.textContent=p.avaliacao||'☆☆☆☆☆';card.appendChild(avaliacao);
   const titulo=document.createElement('h3');titulo.textContent=p.nome;card.appendChild(titulo);
   const preco=document.createElement('p');preco.className='preco';preco.textContent=Number(p.preco).toLocaleString('pt-BR',{style:'currency',currency:'BRL'});card.appendChild(preco);
   if(p.cor){const cor=document.createElement('div');cor.className='cor-seletor';cor.style.backgroundColor=p.cor;card.appendChild(cor);}
   const botao=document.createElement('button');botao.className='btn-adicionar';botao.textContent='Adicionar ao Carrinho';botao.disabled=Number(p.estoque)===0;
   if(botao.disabled)botao.textContent='Indisponível';
   botao.addEventListener('click',()=>adicionarAoCarrinho(p.nome,Number(p.preco)));card.appendChild(botao);
   grade.appendChild(card);
  }
 }catch(e){grade.textContent='Não foi possível carregar os produtos: '+e.message;console.error(e);}
}

function confirmarAcao(mensagem) {
  return new Promise(resolve => {
    const modal = document.getElementById('confirmar-modal');
    document.getElementById('confirmar-texto').textContent = mensagem;
    modal.addEventListener('close', () => resolve(modal.returnValue === 'confirmar'), {once:true});
    modal.returnValue = 'cancelar'; modal.showModal();
  });
}
function finalizarCompra() {
  if (!carrinho.length) { mostrarErro('Seu carrinho está vazio.'); return; }
  mostrarMensagem('Catálogo acadêmico: os itens foram selecionados. Este projeto não processa pagamentos.');
}
window.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('form').forEach(form => form.addEventListener('reset', () => {
    limparErros(form);
    form.querySelectorAll('input[autocomplete="new-password"],#senhaLogin').forEach(input => input.type='password');
    form.querySelectorAll('.toggle-password').forEach(btn => { btn.innerHTML='<i class="fa-solid fa-eye" aria-hidden="true"></i>'; btn.setAttribute('aria-pressed','false'); btn.setAttribute('aria-label','Mostrar senha'); });
  }));
});
