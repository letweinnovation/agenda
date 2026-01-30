<?php
require_once 'config.php';
require_once 'AnalystManager.php';
require_once 'TrelloHelper.php';

$config = require 'config.php';
$analystManager = new AnalystManager('analysts.json');
$trelloHelper = new TrelloHelper($config);
$allAnalysts = $analystManager->getAll();

$error = null;
$message = null;
$foundAnalyst = null;
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'analyst'; // 'analyst' or 'company'

// Handle Direct Analyst Search
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'search_analyst') {
    $name = trim($_POST['analyst_name']);
    if (!empty($name)) {
        $matches = $analystManager->findByName($name);
        if (count($matches) === 1) {
            header("Location: " . $matches[0]['calendar_url']);
            exit;
        } elseif (count($matches) > 1) {
            $error = "Ops! Encontramos mais de um analista com esse nome. Tente ser mais específico.";
        } else {
            $error = "Não encontramos um analista com esse nome.";
        }
    }
}

// Handle Company Search
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'search_company') {
    $companyName = trim($_POST['company_name']);
    $mode = 'company'; // Stay in company mode

    try {
        $cards = $trelloHelper->searchCardByCompanyName($companyName);

        if (empty($cards)) {
            $error = "Não encontramos o nome da sua empresa. Pergunte no grupo pelo nome do responsável.";
        } else {
            $card = $cards[0];
            if (empty($card['idMembers'])) {
                $error = "O card da empresa '" . htmlspecialchars($card['name']) . "' não tem ninguém atribuído.";
            } else {
                $memberId = $card['idMembers'][0];
                $memberDetails = $trelloHelper->getMemberDetails($memberId);

                if ($memberDetails) {
                    $analyst = $analystManager->findByTrelloUsername($memberDetails['username']);
                    if (!$analyst) {
                        $analyst = $analystManager->findByTrelloFullName($memberDetails['fullName']);
                    }

                    if ($analyst) {
                        $foundAnalyst = $analyst;
                        $message = "Encontramos o responsável pela sua implantação!";
                    } else {
                        $error = "O responsável no Trello é '{$memberDetails['fullName']}', mas não temos a agenda dele cadastrada.";
                    }
                } else {
                    $error = "Erro ao buscar detalhes do membro no Trello.";
                }
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GTI PLUG - Agende sua Reunião</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        const analysts = <?= json_encode(array_values($allAnalysts)) ?>;

        function showDropdown(input) {
            const val = input.value.trim().toLowerCase();
            const dropdown = document.getElementById('custom-dropdown');
            const btn = document.getElementById('btn-agendar');

            let filtered = analysts;
            if (val.length >= 2) {
                filtered = analysts.filter(a => a.name.toLowerCase().includes(val));
            }

            // Enable button only on exact match
            const exactMatch = analysts.find(a => a.name.toLowerCase() === val);
            if (exactMatch) {
                btn.disabled = false;
                btn.dataset.url = exactMatch.calendar_url;
                localStorage.setItem('gti_analyst_name', exactMatch.name);
            } else {
                btn.disabled = true;
                btn.dataset.url = '';
            }

            // Build dropdown
            if (val.length >= 2 && filtered.length > 0) {
                dropdown.innerHTML = filtered.map(a =>
                    `<div class="dropdown-item" onmousedown="selectAnalyst('${a.name}', '${a.calendar_url}')">${a.name}</div>`
                ).join('');
                dropdown.style.display = 'block';
            } else if (val.length >= 2 && filtered.length === 0) {
                dropdown.innerHTML = '<div class="dropdown-item no-result">Nenhum resultado encontrado</div>';
                dropdown.style.display = 'block';
            } else {
                dropdown.style.display = 'none';
            }
        }

        function selectAnalyst(name, url) {
            const input = document.getElementById('analyst_name');
            const btn = document.getElementById('btn-agendar');
            input.value = name;
            document.getElementById('custom-dropdown').style.display = 'none';
            btn.disabled = false;
            btn.dataset.url = url;
            localStorage.setItem('gti_analyst_name', name);
        }

        function goToCalendar() {
            const btn = document.getElementById('btn-agendar');
            if (btn.dataset.url) {
                window.location.href = btn.dataset.url;
            }
        }

        function hideDropdown() {
            setTimeout(() => {
                document.getElementById('custom-dropdown').style.display = 'none';
            }, 200);
        }

        document.addEventListener('DOMContentLoaded', function  () {
            const cached = localStorage.getItem('gti_analyst_name');
            const input = document.getElementById('analyst_name');
            if (cached && input) {
                input.value = cached;
                const match = analysts.find(a => a.name.toLowerCase() === cached.toLowerCase());
                if (match) {
                    const btn = document.getElementById('btn-agendar');
                    if (btn) {
                        btn.disabled = false;
                        btn.dataset.url = match.calendar_url;
                    }
                }
            }
        });
    </script>
</head>

<body>

    <div class="container">

        <?php if ($error): ?>
            <div class="alert alert-error">
                <span style="font-size: 1.5rem;">⚠️</span>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($foundAnalyst): ?>
            <!-- RESULT: Analyst Found -->
            <div class="analyst-result">
                <p class="brand-tag">GTI PLUG</p>
                <h1 class="main-title">Encontramos seu responsável!</h1>

                <?php if ($message): ?>
                    <p class="result-message"><?= $message ?></p>
                <?php endif; ?>

                <div class="analyst-card">
                    <p class="analyst-label">Seu analista é</p>
                    <h2 class="analyst-name"><?= htmlspecialchars($foundAnalyst['name']) ?></h2>
                </div>

                <a href="<?= htmlspecialchars($foundAnalyst['calendar_url']) ?>" class="btn" target="_blank">
                    Agendar Reunião <span>➔</span>
                </a>

                <div style="margin-top: 2rem;">
                    <a href="index.php" class="link-back">← Voltar ao início</a>
                </div>
            </div>

        <?php elseif ($mode === 'company'): ?>
            <!-- MODE: Company Search -->
            <form method="POST" action="index.php?mode=company">
                <input type="hidden" name="action" value="search_company">
                <p class="brand-tag">GTI PLUG</p>
                <h1 class="main-title">Buscar por empresa</h1>
                <label for="company_name" class="question-label">Digite o nome da sua empresa</label>

                <div class="form-group">
                    <input type="text" id="company_name" name="company_name" placeholder="Nome da empresa..." required
                        autocomplete="off" autofocus>
                </div>

                <button type="submit" class="btn">Buscar <span>➔</span></button>

                <div style="margin-top: 2rem;">
                    <a href="index.php" class="link-back">← Já sei o nome do responsável</a>
                </div>
            </form>

        <?php else: ?>
            <!-- MODE: Analyst Search (default) -->
            <form id="main-search-form" method="POST" action="index.php" onsubmit="return false;">
                <input type="hidden" name="action" value="search_analyst">
                <p class="brand-tag">GTI PLUG</p>
                <h1 class="main-title">Agende sua reunião</h1>
                <label for="analyst_name" class="question-label">Digite o nome do responsável pela sua implantação</label>

                <div class="form-group autocomplete-wrapper">
                    <input type="text" id="analyst_name" name="analyst_name" placeholder="Digite aqui..." required
                        autocomplete="off" autofocus oninput="showDropdown(this)" onblur="hideDropdown()">
                    <div id="custom-dropdown" class="custom-dropdown"></div>
                </div>

                <button type="button" id="btn-agendar" class="btn" disabled onclick="goToCalendar()">
                    Agendar <span>➔</span>
                </button>

                <div style="margin-top: 2rem;">
                    <a href="index.php?mode=company" class="link-back">Não me lembro do nome → Buscar por empresa</a>
                </div>
            </form>
        <?php endif; ?>
    </div>

</body>

</html>