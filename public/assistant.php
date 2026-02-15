<?php
require_once __DIR__ . '/../lib/db.php';
session_start();
if (!isset($_SESSION['patient_id'])) { header('Location: login.php'); exit; }
$pid = $_SESSION['patient_id'];
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Assistant IA - EasyConsult</title>
  <link rel="stylesheet" href="./style.css">
  <style>
    .assistant-page{max-width:900px;margin:2rem auto;padding:1rem}
    .chat{background:#fff;padding:1rem;border-radius:0.8rem;box-shadow:0 6px 18px rgba(2,6,23,0.06);min-height:320px}
    .messages{max-height:420px;overflow:auto;padding:0.5rem}
    .msg{padding:0.6rem 0.8rem;border-radius:0.6rem;margin-bottom:0.6rem;display:inline-block}
    .msg.user{background:#e6f2ff;color:#003a6b;align-self:flex-end}
    .msg.bot{background:#f3f4f6;color:#1f2937}
    .chat-controls{display:flex;gap:0.5rem;margin-top:0.75rem}
    .chat-input{flex:1;padding:0.75rem;border:1px solid var(--gray-300);border-radius:0.6rem}
  </style>
</head>
<body>
  <?php include __DIR__ . '/../lib/nav.php'; ?>
  <main class="assistant-page">
    <h2>Assistant IA</h2>
    <p>Vous pouvez décrire vos symptômes ou demander des conseils. Mode avancé: configurez un service LLM externe (non configuré par défaut).</p>

    <div class="announcements"><div class="marquee"><span>Conseil: en cas d'urgence, appelez le numéro d'urgence.</span></div></div>

    <div class="chat" id="chat">
      <div class="messages" id="messages"></div>
      <div class="chat-controls">
        <input id="chat-input" class="chat-input" placeholder="Décrivez vos symptômes, ex: fièvre et toux" />
        <button id="send-btn" class="btn btn-primary">Envoyer</button>
      </div>
    </div>

    <div style="margin-top:1rem">
      <h3>Mode Avancé</h3>
      <p>Pour connecter un service IA externe, créez un fichier `lib/ai_config.php` avec vos informations d'API (ne pas le committer). Sinon l'assistant utilisera l'analyse locale et des réponses modèles.</p>
    </div>
  </main>

  <script src="./app.js"></script>
  <script>
    // Simple chat using existing SYMPTOM_ANALYZER on client side and saving via symptom_submit.php
    document.addEventListener('DOMContentLoaded', function(){
      const messagesEl = document.getElementById('messages');
      const input = document.getElementById('chat-input');
      const send = document.getElementById('send-btn');

      function append(msg, cls){
        const d = document.createElement('div'); d.className = 'msg ' + cls; d.textContent = msg; messagesEl.appendChild(d); messagesEl.scrollTop = messagesEl.scrollHeight;
      }

      send.addEventListener('click', async function(){
        const text = input.value.trim(); if(!text) return; append(text,'user'); input.value='';

        // First try local client-side analyzer (from app.js)
        if (typeof SYMPTOM_ANALYZER !== 'undefined'){
          const res = SYMPTOM_ANALYZER.analyze(text);
          append(res.response,'bot');
        } else {
          append('Analyse locale non disponible. Envoi au serveur...','bot');
        }

        // Save to server via existing endpoint (stores in DB)
        try {
          const r = await fetch('symptom_submit.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ text }) });
          const j = await r.json();
          if (j.message) append('Enregistré: '+j.message,'bot');
        } catch (e) {
          append('Erreur sauvegarde: '+(e.message||e),'bot');
        }
      });
    });
  </script>
</body>
</html>
