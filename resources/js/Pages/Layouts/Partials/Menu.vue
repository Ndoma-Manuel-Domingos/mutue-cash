<template>
  <nav class="mt-2">
    <ul
      class="nav nav-pills nav-sidebar flex-column"
      data-widget="treeview"
      role="menu"
      data-accordion="false"
    >
      <li class="nav-item">
        <Link
          :href="route('mc.dashboard')"
          class="nav-link"
          :class="{ active: $page.component == 'Dashboard' }"
        >
          <i class="nav-icon fas fa-home"></i>
          <p>Dashboard</p>
        </Link>
      </li>

      <li class="nav-item" title="OPERAÇÕES">
        <a
          href="#"
          class="nav-link"
          :class="{ active: $page.component.startsWith('Operacoes/') }"
        >
          <i class="nav-icon fas fa-cogs"></i>
          <p>
            Operações
            <i class="right fas fa-angle-left"></i>
          </p>
        </a>
        <ul class="nav nav-treeview">
          <li class="nav-item" title="DEPOSITOS">
            <Link
              :href="route('mc.depositos.index')"
              class="nav-link"
              :class="{
                active:
                  $page.component == 'Operacoes/Depositos/Index',
              }"
            >
              <i class="far fa-circle nav-icon"></i>
              <p>Deposito</p>
            </Link>
          </li>
          <li class="nav-item" title="PAGAMENTOS">
            <Link
              :href="route('mc.pagamentos.index')"
              class="nav-link"
              :class="{
                active: $page.component == 'Operacoes/Pagamentos/Index',
              }"
            >
              <i class="far fa-circle nav-icon"></i>
              <p>Pagamentos</p>
            </Link>
          </li>

        </ul>
      </li>

      <li class="nav-item">
        <a
          href="#"
          class="nav-link"
          title="RELATÓRIOS"
          :class="{
            active: $page.component.startsWith('Relatorios/'),
          }"
        >
          <i class="nav-icon fas fa-file"></i>
          <p>
            Relatórios
            <i class="right fas fa-angle-left"></i>
          </p>
        </a>
        <ul class="nav nav-treeview">
          <li class="nav-item" title="FECHO DO CAIXA POR OPERADOR">
            <Link
              :href="route('mc.fecho-caixa-operador.index')"
              class="nav-link"
              :class="{
                active:
                  $page.component == 'Relatorios/FechoCaixa/Operador',
              }"
            >
              <i class="far fa-circle nav-icon"></i>
              <p>Fecho Caixa Operador</p>
            </Link>
          </li>
        </ul>
      </li>

      <div class="ml-auto">
        <ul class="navbar-nav">
          <li class="nav-item text-left">
            <Link
              class="nav-link btn btn-link btn-danger text-white"
              href="/logout"
              method="post"
              as="button"
              type="button"
            >
              <i class="fas fa-sign-out-alt"></i>
              Sair
            </Link>
          </li>
        </ul>
      </div>


    </ul>
  </nav>
</template>

<script>
import { Link } from "@inertiajs/inertia-vue3";
export default {
  components: {
    Link,
  },
};
</script>


<style>
.nav-pills .nav-link.active,
.nav-pills .show > .nav-link {
  color: #fff;
  background-color: #52c7ed;
}
</style>
