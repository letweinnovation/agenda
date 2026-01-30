# Agenda do Analista de Onboarding

Aplicação simples para redirecionar clientes para a agenda de seus analistas de entrega.

## Configuração

1.  **Credenciais do Trello**:
    *   Abra o arquivo `config.php`.
    *   Preencha `trello_api_key`, `trello_token` e `trello_board_id`.
    *   Você pode obter sua chave e token em: https://trello.com/app-key

2.  **Dados dos Analistas**:
    *   O arquivo `analysts.json` já contém Isadora e Walyson.
    *   Para a integração automática funcionar, o `trello_username` no JSON deve corresponder ao usuário do Trello do analista.

## Como Rodar Localmente

Se você tiver PHP instalado, pode testar rapidamente usando o servidor embutido:

```bash
php -S localhost:8000
```

Acesse `http://localhost:8000` no seu navegador.
