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
              class="nav-link" :class="{active: $page.component == 'Operacoes/Pagamentos/Index',}">
              <i class="far fa-circle nav-icon"></i>
              <p>Pagamentos</p>
            </Link>
          </li>
          <!-- <li class="nav-item" title="CRIAR CAIXAS">
            <Link
              href="/operacoes/caixas"
              class="nav-link" :class="{active: $page.component == 'Operacoes/Caixas/Index',}">
              <i class="far fa-circle nav-icon"></i>
              <p>Criar Caixas</p>
            </Link>
          </li> -->

        </ul>
      </li>
      
      <li class="nav-item" title="MOVIMENTOS">
        <a
          href="#"
          class="nav-link"
          :class="{ active: $page.component.startsWith('Operacoes/Movimentos') }"
        >
          <i class="nav-icon fas fa-box"></i>
          <p>
            Movimentos
            <i class="right fas fa-angle-left"></i>
          </p>
        </a>
        <ul class="nav nav-treeview">
          <li class="nav-item" title="ABERTURA DO CAIXA">
            <Link
              :href="route('mc.movimentos-abertura-caixa')"
              class="nav-link"
              :class="{
                active:
                  $page.component == 'Operacoes/Movimentos/Abertura',
              }"
            >
              <i class="far fa-circle nav-icon"></i>
              <p>Abertura Caixa</p>
            </Link>
          </li>
          <li class="nav-item" title="FECHO DE CAIXA">
            <Link
              :href="route('mc.movimentos-fecho-caixa')"
              class="nav-link"
              :class="{
                active: $page.component == 'Operacoes/Movimentos/Fecho',
              }"
            >
              <i class="far fa-circle nav-icon"></i>
              <p>Fecho do Caixa</p>
            </Link>
          </li>
          
          
          <li class="nav-item" title="VALIDAR FECHO DE CAIXA" v-if="user.type_user == 'Administrador'">
            <Link
              :href="route('mc.movimentos-validar-fecho-caixa')"
              class="nav-link"
              :class="{
                active: $page.component == 'Operacoes/Movimentos/ValidarFechoCaixa',
              }"
            >
              <i class="far fa-circle nav-icon"></i>
              <p>Validar Fecho do Caixa</p>
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
          
          <li class="nav-item" title="EXTRATOS DE DEPÓSITOS">
            <Link
              :href="route('mc.extrato-depositos.index')"
              class="nav-link"
              :class="{
                active:
                  $page.component == 'Relatorios/FechoCaixa/Extrato-Depositos',
              }"
            >
              <i class="far fa-circle nav-icon"></i>
              <p>Extrato de Depósitos</p>
            </Link>
          </li>
          
          <li class="nav-item" title="EXTRATOS DOS PAGAMENTOS">
            <Link
              :href="route('mc.extrato-pagamentos.index')"
              class="nav-link"
              :class="{
                active:
                  $page.component == 'Relatorios/FechoCaixa/Extrato-Pagamentos',
              }"
            >
              <i class="far fa-circle nav-icon"></i>
              <p>Extrato de Pagamentos</p>
            </Link>
          </li>
          
        </ul>
      </li>

      <div class="ml-auto">
        <ul class="navbar-nav">
          <li class="nav-item text-left">
              <!-- href="/logout"
              method="post" -->
            <Link
              class="nav-link btn btn-link btn-danger text-white"
              as="button"
              type="button"
              @click="logout"
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

<script setup>
  import { computed } from "vue";
  import { usePage } from "@inertiajs/inertia-vue3";
  import { Link } from "@inertiajs/inertia-vue3";

  const user = computed(() => {
    return usePage().props.value.auth.user;
  });
</script>

<script>

  export default {
    methods: {
      logout(){
        axios
          .post("/logout")
          .then((response) => {
              Swal.fire({
                icon: "warning",
                title: "Atenção...",
                text: response.data.message,
              });
          })
          .catch((error) => {
            console.error(error);
          });
        
      }
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


