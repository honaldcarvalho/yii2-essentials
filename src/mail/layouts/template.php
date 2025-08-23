<!doctype html>
<html>
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <style>
      img {
        border: none;
        -ms-interpolation-mode: bicubic;
        max-width: 100%;
      }

      body {
        background-color: #1e1e2d; /* dark background */
        font-family: sans-serif;
        -webkit-font-smoothing: antialiased;
        font-size: 14px;
        line-height: 1.4;
        margin: 0;
        padding: 0;
        color: #e9ecef; /* light text */
      }

      table {
        border-collapse: separate;
        mso-table-lspace: 0pt;
        mso-table-rspace: 0pt;
        width: 100%;
      }
      table td {
        font-family: sans-serif;
        font-size: 14px;
        vertical-align: top;
        color: #e9ecef;
      }

      .body {
        background-color: #1e1e2d;
        width: 100%;
      }

      .container {
        display: block;
        margin: 0 auto !important;
        max-width: 580px;
        padding: 10px;
        width: 580px;
      }

      .content {
        box-sizing: border-box;
        display: block;
        margin: 0 auto;
        max-width: 580px;
        padding: 10px;
      }

      .main {
        background: #2c2f3e; /* card dark bg */
        border-radius: 6px;
        width: 100%;
      }

      .wrapper {
        box-sizing: border-box;
        padding: 20px;
      }

      .footer {
        clear: both;
        margin-top: 10px;
        text-align: center;
        width: 100%;
      }
      .footer td,
      .footer p,
      .footer span,
      .footer a {
        color: #6c757d;
        font-size: 12px;
        text-align: center;
      }

      h1,
      h2,
      h3,
      h4 {
        color: #f8f9fa;
        font-weight: 400;
        line-height: 1.4;
        margin: 0 0 20px;
      }

      h1 {
        font-size: 28px;
        font-weight: 500;
        text-align: center;
      }

      p,
      ul,
      ol {
        font-size: 14px;
        font-weight: normal;
        margin: 0 0 15px;
      }

      a {
        color: #0d6efd;
        text-decoration: underline;
      }

      .btn a {
        background-color: #0d6efd;
        border: solid 1px #0d6efd;
        border-radius: 5px;
        box-sizing: border-box;
        color: #ffffff !important;
        cursor: pointer;
        display: inline-block;
        font-size: 14px;
        font-weight: bold;
        margin: 0;
        padding: 12px 25px;
        text-decoration: none;
        text-transform: capitalize;
      }

      .btn-primary a:hover {
        background-color: #0b5ed7 !important;
        border-color: #0b5ed7 !important;
      }

      hr {
        border: 0;
        border-bottom: 1px solid #3a3f51;
        margin: 20px 0;
      }

      @media only screen and (max-width: 620px) {
        table.body h1 {
          font-size: 24px !important;
        }
        table.body p,
        table.body ul,
        table.body ol,
        table.body td,
        table.body span,
        table.body a {
          font-size: 16px !important;
        }
      }
    </style>
  </head>
  <body>
    <span class="preheader"></span>
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="body">
      <tr>
        <td>&nbsp;</td>
        <td class="container">
          <div class="content">

            <table role="presentation" class="main">
              <tr>
                <td class="x_header" style="padding:20px; text-align:center">
                  <a href="https://croacworks.com.br" target="_blank" rel="noopener noreferrer" style="color:#f8f9fa; font-size:19px; font-weight:bold; text-decoration:none; display:inline-block">
                    <img src="https://croacworks.com.br/images/croacworks-logo-hq.png" style="border:none; width:150px;">
                  </a>
                </td>
              </tr>
              <tr>
                <td class="wrapper">
                  <?= $content ?>
                </td>
              </tr>
            </table>

            <div class="footer">
              <table role="presentation">
                <tr>
                  <td class="content-block powered-by">
                    <a href="https://croacworks.com.br/">CroacWorks - Jumping from idea to result with style and innovation.</a>
                  </td>
                </tr>
              </table>
            </div>

          </div>
        </td>
        <td>&nbsp;</td>
      </tr>
    </table>
  </body>
</html>
