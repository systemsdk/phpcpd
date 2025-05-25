<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"  xmlns:cpd="https://systemsdk.github.io/phpcpd/report" exclude-result-prefixes="cpd">
<xsl:output method="html" doctype-system="about:legacy-compat"/>
<xsl:template match="/">
<html>
<head>
  <meta charset="utf-8"/>

  <!-- Dependencies:
  https://getbootstrap.com/docs/5.3/getting-started/download/
  https://datatables.net/download/ (Styling Bootstrap5 + jQuery + DataTables + Buttons + Column Visibility + HTML5 Export + JSZip + pdfmake + Print view
  -->

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous"/>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"/>
  <link href="https://cdn.datatables.net/v/bs5/jq-3.7.0/jszip-3.10.1/dt-2.3.1/b-3.2.3/b-colvis-3.2.3/b-html5-3.2.3/b-print-3.2.3/datatables.min.css" rel="stylesheet" integrity="sha384-ffA5YILdZwUQB7+HlHwFucF4hce41j8Tt6mvi1e0CLeZ0d9LqFUQJDx5vtwqZKah" crossorigin="anonymous"/>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js" integrity="sha384-VFQrHzqBh5qiJIU0uGU5CIW3+OWpdGGJM9LBnGbuIH2mkICcFZ7lPd/AAtI7SNf7" crossorigin="anonymous"/>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js" integrity="sha384-/RlQG9uf0M2vcTw3CX7fbqgbj/h8wKxw7C3zu9/GxcBPRKOEcESxaxufwRXqzq6n" crossorigin="anonymous"/>
  <script src="https://cdn.datatables.net/v/bs5/jq-3.7.0/jszip-3.10.1/dt-2.3.1/b-3.2.3/b-colvis-3.2.3/b-html5-3.2.3/b-print-3.2.3/datatables.min.js" integrity="sha384-C5Q/xdoW8seeaT5jRMV9lAiJQNoGaRaJkyJ/A7lEdXF4pYx8lQjdNBXQ/KlbQA83" crossorigin="anonymous"/>
  <title>PHPCPD Report</title>
  <link rel="icon" href="https://systemsdk.github.io/images/favicon.ico" sizes="32x32" />

</head>
<body style="padding-top: 4.5rem;">
  <nav class="navbar navbar-expand-lg fixed-top navbar-dark bg-dark">
      <a class="navbar-brand" href="#"><img alt="Logo" src="https://systemsdk.github.io/images/logo-white-small.png"/></a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link active" href="#">Report</a>
          </li>
          <li class="nav-item" id="nav_enable_datatable">
            <a class="nav-link" href="?d=">Enable datatable</a>
          </li>
          <li class="nav-item" id="nav_disable_datatable">
            <a class="nav-link" href="?">Disable datatable</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" target="_blank" rel="noopener noreferrer" href="https://packagist.org/packages/systemsdk/phpcpd">Package page</a>
          </li>
        </ul>
      </div>
  </nav>

    <div class="container">
      <div class="row">
        <div class="col">
            <h4>PHPCPD <font color="#0000FF"><xsl:value-of select="cpd:pmd-cpd/attribute::phpcpdVersion"/></font> - Summary of duplicated code</h4>
            <p>This page summarizes the code fragments that have been found at <font color="#0000FF"><xsl:value-of select="cpd:pmd-cpd/attribute::timestamp"/></font> to be replicated in the code.</p>

          <table class="table table-light table-bordered table-striped table-hover">
            <tr>
              <th># Duplications</th>
              <th>Total lines</th>
              <th>Total tokens</th>
              <th>Approximate number of bytes</th>
            </tr>
            <tr>
              <td class="SummaryNumber"><xsl:value-of select="count(//cpd:duplication)"/></td>
              <td class="SummaryNumber"><xsl:value-of select="sum(//cpd:duplication/@lines)"/></td>
              <td class="SummaryNumber"><xsl:value-of select="sum(//cpd:duplication/@tokens)"/></td>
              <td class="SummaryNumber"><xsl:value-of select="sum(//cpd:duplication/@tokens) * 4"/></td>
            </tr>
          </table>
        </div>
      </div>

      <div class="row">
        <div class="col">
          <h4>Details of duplicated code</h4>
        </div>
      <table style="width:100%" id="data_table" class="table table-light table-bordered table-striped table-hover">
          <thead>
            <tr>
              <th>lines</th>
              <th>tokens</th>
              <th>files</th>
              <th>codefragment</th>
            </tr>
          </thead>
          <tbody>
            <xsl:apply-templates select="cpd:pmd-cpd/cpd:duplication" />
          </tbody>
      </table>
    </div>
  </div>

<script>
  let params = (new URL(document.location)).searchParams;
  let showDatatable = false;

  //------------ can be called with this parameter d
  if (params.get('d') !== null) { // got it via query param d
      showDatatable = true;
  }

  if (showDatatable)  {
    $("#nav_disable_datatable").show();
    $("#nav_enable_datatable").hide();
    $(document).ready( function () {
        $('#data_table').DataTable({
          layout: {
              topStart: ['buttons', 'pageLength']
          },
          buttons: [
              {
                  extend: 'copy',
                  text: ' Copy',
                  exportOptions: {
                      trim: false,
                      stripNewlines: false
                  }
              },
              {
                  extend: 'csv',
                  text: ' CSV',
                  exportOptions: {
                      trim: false,
                      stripNewlines: false
                  }
              },
              {
                  extend: 'excel',
                  text: ' Excel',
                  exportOptions: {
                      trim: false,
                      stripNewlines: false
                  }
              },
              {
                  extend: 'pdf',
                  text: ' PDF',
                  exportOptions: {
                      trim: false,
                      stripNewlines: false
                  }
              },
              {
                  extend: 'print',
                  text: 'Print',
                  exportOptions: {
                      stripHtml: false,
                      stripNewlines: false
                  }
              }
          ]
        });
    });
  } else {
    $("#nav_disable_datatable").hide();
    $("#nav_enable_datatable").show();
  }
</script>

</body>
</html>
</xsl:template>

<!-- templates -->
<xsl:template match="cpd:pmd-cpd/cpd:duplication">
  <xsl:for-each select=".">
      <tr>
        <td><xsl:value-of select="@lines"/></td>
        <td><xsl:value-of select="@tokens"/></td>
        <td>
          <table class="table table-light table-bordered table-striped table-hover">
            <tr><th>line</th><th>endline</th><th>path</th></tr>
            <xsl:for-each select="cpd:file">
              <tr>
                <td><xsl:value-of select="@line"/></td>
                <td><xsl:value-of select="@endline"/></td>
                <td><xsl:value-of select="@path"/></td>
              </tr>
            </xsl:for-each>
          </table>
        </td>
        <td><pre><xsl:value-of select="cpd:codefragment"/></pre></td>
      </tr>
  </xsl:for-each>
</xsl:template>

</xsl:stylesheet>
