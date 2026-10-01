<?php
/**
 * Template Name: Relatório de Cliques
 * @package Os 10 Melhores Livros
 * @since 0.0.1
 */
get_header("report");
// grv_update_field('report_log', json_encode([]), 'option');
?>
<style>
  body {
    font-family: sans-serif;
    padding: 20px;
    background-color: #f0f2f5;
  }

  h1 {
    margin-bottom: 10px;
  }

  .summary {
    margin-bottom: 20px;
    font-size: 18px;
    font-weight: bold;
    flex-wrap: wrap;
    display: flex;
    gap: 10px;
  }

  .summary span {
    display: inline-block;
    background: #fff;
    padding: 8px 12px;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  }

  .report {
    margin-top: 30px;
    overflow-x: auto;
  }

  table {
    width: 100%;
    min-width: 600px;
    border-collapse: collapse;
    background-color: white;
  }

  th, td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #ccc;
    font-size: 14px;
    white-space: nowrap;
  }

  th {
    background-color: #007bff;
    color: white;
    position: sticky;
    top: 0;
    z-index: 1;
  }

  tbody tr:hover {
    background-color: #f1f1f1;
  }

  .link-actions {
    display: flex;
    gap: 6px;
    align-items: center;
  }

  .link-btn {
    font-size: 14px;
    padding: 2px 6px;
    border: none;
    background: #e7e9ec;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    color: #333;
  }

  .link-btn:hover {
    background: #d6d8db;
  }

  .tooltip-wrapper {
    position: relative;
    display: inline-block;
  }

.tooltip {
    visibility: hidden;
    background-color: #333;
    color: #fff;
    text-align: left;
    padding: 6px 10px;
    border-radius: 4px;
    position: absolute;
    z-index: 10;
    bottom: -99px;
    left: 0;
    font-size: 12px;
    opacity: 0;
    transition: opacity 0.3s;
    max-width: 300px;
    word-wrap: break-word;
    white-space: normal;
}


  .tooltip-wrapper:hover .tooltip {
    visibility: visible;
    opacity: 1;
  }

  .link-preview-text {
    max-width: 140px;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    display: inline-block;
  }

  .box-info {
    background: #fff;
    padding: 15px 20px;
    margin-bottom: 20px;
    border-left: 6px solid #007bff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    border-radius: 6px;
  }

  .box-info ul {
    margin: 0;
    padding-left: 18px;
    line-height: 1.6;
  }

  @media screen and (max-width: 600px) {
    body {
      padding: 10px;
    }

    table {
      font-size: 13px;
    }

    th, td {
      padding: 8px;
    }

    .summary {
      font-size: 16px;
    }

    .link-preview-text {
      max-width: 100px;
    }
  }
</style>
</head>
<body>

<h1>Relatório de Cliques</h1>

<?php
$json = grv_get_field('report_log', 'option');
$registros = json_decode($json, true);

$contagem = [
  'ads-1' => 0,
  'ads-2' => 0,
  'ads-3' => 0
];

if (!empty($registros) && is_array($registros)) {
  foreach ($registros as $item) {
    $anuncio_id = $item['anuncio_id'] ?? '';
    if (isset($contagem[$anuncio_id])) {
      $contagem[$anuncio_id]++;
    }
  }
}

$total_rodape = $contagem['ads-1'];
$total_modal  = $contagem['ads-2'];
$total_fechar = $contagem['ads-3'];

$total_anuncios = $total_rodape + $total_modal;

$taxa_rodape = $total_fechar > 0 ? round(($total_rodape / $total_fechar) * 100, 1) : 0;
$taxa_modal  = $total_fechar > 0 ? round(($total_modal / $total_fechar) * 100, 1) : 0;
?>

<div class="summary">
  <span>🔵 Rodapé: <?= $total_rodape ?></span>
  <span>🟣 Modal: <?= $total_modal ?></span>
  <span>🔴 Fechou anúncio: <?= $total_fechar ?></span>
</div>

<div class="box-info">
  <p><strong>📊 Estatísticas:</strong></p>
  <ul>
    <li>📈 Conversão Rodapé: <strong><?= $taxa_rodape ?>%</strong></li>
    <li>📈 Conversão Modal: <strong><?= $taxa_modal ?>%</strong></li>
    <li>📊 Média: a cada <strong><?= $total_fechar > 0 ? round($total_fechar / max(1, $total_rodape), 1) : '0' ?></strong> fechamentos → 1 clique no <strong>Rodapé</strong></li>
    <li>📊 Média: a cada <strong><?= $total_fechar > 0 ? round($total_fechar / max(1, $total_modal), 1) : '0' ?></strong> fechamentos → 1 clique no <strong>Modal</strong></li>
  </ul>
</div>

<div class="report">
  <table>
    <thead>
      <tr>
        <th>🔘</th>
        <th>Data e hora</th>
        <th>Link</th>
        <th>Sistema Operacional</th>
      </tr>
    </thead>
    <tbody>
      <?php
      if (!empty($registros) && is_array($registros)) :
        foreach (array_reverse($registros) as $item) :
          $emoji = match ($item['anuncio_id'] ?? '') {
            'ads-1' => '🔵 rodapé',
            'ads-2' => '🟣 modal',
            'ads-3' => '🔴 fechou',
            default => '⚪️'
          };

          $dataHora = ($item['data'] ?? '-') . ' ' . ($item['hora'] ?? '-');
          $link = esc_url($item['link'] ?? '#');

          $url_parts = parse_url($link);
          $caminho = ($url_parts['path'] ?? '') .
                     (isset($url_parts['query']) ? '?' . $url_parts['query'] : '') .
                     (isset($url_parts['fragment']) ? '#' . $url_parts['fragment'] : '');
      ?>
        <tr>
          <td><?= $emoji ?></td>
          <td><?= esc_html($dataHora) ?></td>
          <td>
            <div class="link-actions">
              <div class="tooltip-wrapper">
                <button class="link-btn">🔍</button>
                <span class="tooltip"><?= esc_html($link) ?></span>
              </div>
              <span class="link-preview-text" title="<?= esc_attr($link) ?>"><?= esc_html($caminho ?: '/') ?></span>
              <a href="<?= $link ?>" class="link-btn" target="_blank" title="Abrir em nova aba">↗️</a>
            </div>
          </td>
          <td><?= esc_html($item['sistema_operacional'] ?? '-') ?></td>
        </tr>
      <?php
        endforeach;
      else :
      ?>
        <tr><td colspan="4">Nenhum clique registrado ainda.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php get_footer("report"); ?>
