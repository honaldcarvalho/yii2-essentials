<div class="sidebar sidebar-dark sidebar-fixed border-end" id="sidebar">
  <div class="sidebar-header border-bottom">
    <div class="sidebar-brand">
      <i class="fas fa-frog fa-2x"></i> <!-- exemplo de logo -->
    </div>
    <button class="btn-close d-lg-none" type="button" aria-label="Close"
      onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()"></button>
  </div>
  <ul class="sidebar-nav" data-coreui="navigation" data-simplebar>
    <li class="nav-item">
      <a class="nav-link" href="index.html">
        <i class="fas fa-tachometer-alt nav-icon"></i>
        Dashboard <span class="badge badge-sm bg-info ms-auto">NEW</span>
      </a>
    </li>

    <li class="nav-title">Theme</li>
    <li class="nav-item">
      <a class="nav-link" href="colors.html">
        <i class="fas fa-tint nav-icon"></i>
        Colors
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="typography.html">
        <i class="fas fa-pencil-alt nav-icon"></i>
        Typography
      </a>
    </li>

    <li class="nav-title">Components</li>
    <li class="nav-group">
      <a class="nav-link nav-group-toggle" href="#">
        <i class="fas fa-puzzle-piece nav-icon"></i>
        Base
      </a>
      <ul class="nav-group-items compact">
        <li class="nav-item"><a class="nav-link" href="base/accordion.html"><i class="far fa-circle nav-icon"></i> Accordion</a></li>
        <li class="nav-item"><a class="nav-link" href="base/breadcrumb.html"><i class="far fa-circle nav-icon"></i> Breadcrumb</a></li>
        <li class="nav-item"><a class="nav-link" href="base/cards.html"><i class="far fa-circle nav-icon"></i> Cards</a></li>
        <li class="nav-item"><a class="nav-link" href="base/tables.html"><i class="far fa-circle nav-icon"></i> Tables</a></li>
      </ul>
    </li>

    <li class="nav-group">
      <a class="nav-link nav-group-toggle" href="#">
        <i class="fas fa-mouse-pointer nav-icon"></i>
        Buttons
      </a>
      <ul class="nav-group-items compact">
        <li class="nav-item"><a class="nav-link" href="buttons/buttons.html"><i class="far fa-circle nav-icon"></i> Buttons</a></li>
        <li class="nav-item"><a class="nav-link" href="buttons/dropdowns.html"><i class="far fa-circle nav-icon"></i> Dropdowns</a></li>
      </ul>
    </li>

    <li class="nav-item">
      <a class="nav-link" href="charts.html">
        <i class="fas fa-chart-pie nav-icon"></i>
        Charts
      </a>
    </li>

    <li class="nav-group">
      <a class="nav-link nav-group-toggle" href="#">
        <i class="fas fa-bell nav-icon"></i>
        Notifications
      </a>
      <ul class="nav-group-items compact">
        <li class="nav-item"><a class="nav-link" href="notifications/alerts.html"><i class="far fa-circle nav-icon"></i> Alerts</a></li>
        <li class="nav-item"><a class="nav-link" href="notifications/modals.html"><i class="far fa-circle nav-icon"></i> Modals</a></li>
      </ul>
    </li>

    <li class="nav-item">
      <a class="nav-link" href="widgets.html">
        <i class="fas fa-calculator nav-icon"></i>
        Widgets <span class="badge badge-sm bg-info ms-auto">NEW</span>
      </a>
    </li>

    <li class="nav-divider"></li>
    <li class="nav-title">Extras</li>
    <li class="nav-group">
      <a class="nav-link nav-group-toggle" href="#">
        <i class="fas fa-star nav-icon"></i>
        Pages
      </a>
      <ul class="nav-group-items compact">
        <li class="nav-item"><a class="nav-link" href="login.html"><i class="fas fa-sign-out-alt nav-icon"></i> Login</a></li>
        <li class="nav-item"><a class="nav-link" href="register.html"><i class="fas fa-user-plus nav-icon"></i> Register</a></li>
        <li class="nav-item"><a class="nav-link" href="404.html"><i class="fas fa-bug nav-icon"></i> Error 404</a></li>
        <li class="nav-item"><a class="nav-link" href="500.html"><i class="fas fa-bug nav-icon"></i> Error 500</a></li>
      </ul>
    </li>

    <li class="nav-item mt-auto">
      <a class="nav-link" href="https://coreui.io/bootstrap/docs/templates/installation/" target="_blank">
        <i class="fas fa-file-alt nav-icon"></i>
        Docs
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link text-primary fw-semibold" href="https://coreui.io/product/bootstrap-dashboard-template/" target="_top">
        <i class="fas fa-layer-group nav-icon text-primary"></i>
        Try CoreUI PRO
      </a>
    </li>
  </ul>

  <div class="sidebar-footer border-top d-none d-md-flex">     
    <button class="sidebar-toggler" type="button" data-coreui-toggle="unfoldable"></button>
  </div>
</div>
