<template>
  <nav class="mt-2">
    <ul
      class="nav nav-pills nav-sidebar flex-column"
      data-widget="treeview"
      role="menu"
      data-accordion="false"
    >
      <li class="nav-item">
        <!-- <li class="nav-item" v-if="user.roles['Gestor de Caixa']"> -->

        <Link
          href="/dashboard"
          class="nav-link"
          :class="{
            active: $page.component == 'Dashboard',
          }"
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
          <li
            class="nav-item"
            title="DEPOSITOS"
            v-if="user.auth.can['listar deposito']"
          >
            <Link
              href="/depositos"
              class="nav-link"
              :class="{
                active: $page.component == 'Operacoes/Depositos/Index',
              }"
            >
              <i class="far fa-circle nav-icon"></i>
              <p>Deposito</p>
            </Link>
          </li>
          <li
            class="nav-item"
            title="PAGAMENTOS"
            v-if="user.auth.can['listar pagamento']"
          >
            <Link
              href="/pagamentos"
              class="nav-link"
              :class="{
                active: $page.component == 'Operacoes/Pagamentos/Index',
              }"
            >
              <i class="far fa-circle nav-icon"></i>
              <p>Pagamentos</p>
            </Link>
          </li>

          <!-- <li class="nav-item" title="REGISTRAR SAIDAS">
            <Link
              href="/movimentos/registrar-saidas"
              class="nav-link" :class="{active: $page.component == 'Operacoes/Movimentos/RegistrarSaida',}">
              <i class="far fa-circle nav-icon"></i>
              <p>Registrar Saída</p>
            </Link>
          </li> -->

          <li
            class="nav-item"
            title="CRIAR CAIXAS"
            v-if="user.auth.can['listar caixa']"
          >
            <Link
              href="/operacoes/caixas"
              class="nav-link"
              :class="{ active: $page.component == 'Operacoes/Caixas/Index' }"
            >
              <i class="far fa-circle nav-icon"></i>
              <p>Lista dos Caixas</p>
            </Link>
          </li>
        </ul>
      </li>

      <li class="nav-item" title="MOVIMENTOS">
        <a
          href="#"
          class="nav-link"
          :class="{
            active: $page.component.startsWith('Operacoes/Movimentos'),
          }"
        >
          <i class="nav-icon fas fa-box"></i>
          <p>
            Movimentos
            <i class="right fas fa-angle-left"></i>
          </p>
        </a>
        <ul class="nav nav-treeview">
          <li
            class="nav-item"
            title="CAIXAS ABERTOS"
            v-if="user.auth.can['visualizar caixa abertos']"
          >
            <Link
              href="/movimentos/caixas-abertos"
              class="nav-link"
              :class="{
                active: $page.component == 'Operacoes/Movimentos/Abertura',
              }"
            >
              <i class="far fa-circle nav-icon"></i>
              <p>Caixas Abertos</p>
            </Link>
          </li>

          <li
            class="nav-item"
            title="ABERTURA DO CAIXA"
            v-if="user.auth.can['relatorio diario caixa']"
          >
            <Link
              href="/movimentos/diarios-operador"
              class="nav-link"
              :class="{
                active:
                  $page.component == 'Operacoes/Movimentos/Diario-Operador',
              }"
            >
              <i class="far fa-circle nav-icon"></i>
              <p>Diários</p>
            </Link>
          </li>

          <li
            class="nav-item"
            title="ABERTURA DO CAIXA"
            v-if="user.auth.can['abertura caixa']"
          >
            <Link
              href="/movimentos/abertura-caixa"
              class="nav-link"
              :class="{
                active: $page.component == 'Operacoes/Movimentos/Abertura',
              }"
            >
              <i class="far fa-circle nav-icon"></i>
              <p>Abertura Caixa</p>
            </Link>
          </li>

          <li
            class="nav-item"
            title="FECHO DE CAIXA"
            v-if="user.auth.can['fecho caixa']"
          >
            <Link
              href="/movimentos/fecho-caixa"
              class="nav-link"
              :class="{
                active: $page.component == 'Operacoes/Movimentos/Fecho',
              }"
            >
              <i class="far fa-circle nav-icon"></i>
              <p>Fecho do Caixa</p>
            </Link>
          </li>

          <li
            class="nav-item"
            title="VALIDAR FECHO DE CAIXA"
            v-if="user.auth.can['validar caixa']"
          >
            <Link
              href="/movimentos/validar-fecho"
              class="nav-link"
              :class="{
                active:
                  $page.component == 'Operacoes/Movimentos/ValidarFechoCaixa',
              }"
            >
              <i class="far fa-circle nav-icon"></i>
              <p>Validar Fecho do Caixa</p>
            </Link>
          </li>
        </ul>
      </li>

      <li class="nav-item" v-if="user.auth.can['listar relatorio']">
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
              href="/relatorios/fecho-caixa/operador"
              class="nav-link"
              :class="{
                active: $page.component == 'Relatorios/FechoCaixa/Operador',
              }"
            >
              <i class="far fa-circle nav-icon"></i>
              <p>Extrato por Operador</p>
            </Link>
          </li>

          <li
            class="nav-item"
            title="EXTRATOS DE DEPÓSITOS"
            v-if="user.auth.can['extrato deposito']"
          >
            <Link
              href="/relatorios/extrato-depositos"
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

          <li
            class="nav-item"
            title="EXTRATOS DOS PAGAMENTOS"
            v-if="user.auth.can['extrato pagamento']"
          >
            <Link
              href="/relatorios/extrato-pagamentos"
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

          <!--   v-if="user.auth.can['LISTAGEM LOGS ACESSO']"-->
          <li
            class="nav-item"
            title="LISTAGEM LOGS"
          
          >
            <Link
              href="/relatorios/listar-loggs-acesso"
              class="nav-link"
              :class="{
                active:
                  $page.component ==
                  'Relatorios/FechoCaixa/ListarLoggsAcesso',
              }"
            >
              <i class="far fa-circle nav-icon"></i>
              <p>Listar Loggs</p>
            </Link>
          </li>
        </ul>
      </li>

      <li class="nav-item" v-if="user.auth.can['criar operador']">
        <a
          href="#"
          class="nav-link"
          title="UTILIZADORES"
          :class="{
            active: $page.component.startsWith('Utilizadores/'),
          }"
        >
          <i class="nav-icon fas fa-users"></i>
          <p>
            Gestão de Utilizadores
            <i class="right fas fa-angle-left"></i>
          </p>
        </a>
        <ul class="nav nav-treeview">
          <li
            class="nav-item"
            title="GESTÃO DE UTILIZADORES"
            v-if="user.auth.can['criar operador']"
          >
            <Link
              href="/roles/index"
              class="nav-link"
              :class="{
                active: $page.component == 'Utilizadores/Roles/Index',
              }"
            >
              <i class="far fa-circle nav-icon"></i>
              <p>Perfil</p>
            </Link>
          </li>

          <li
            class="nav-item"
            title="DEFINIR PERMISSIONS OPERADOR"
            v-if="user.auth.can['criar operador']"
          >
            <Link
              href="/roles/utilizadores"
              class="nav-link"
              :class="{
                active: $page.component == 'Utilizadores/Index',
              }"
            >
              <i class="far fa-circle nav-icon"></i>
              <p>Utilizadores</p>
            </Link>
          </li>
        </ul>
      </li>

      <!-- <div class="ml-auto" >
        <ul class="navbar-nav">
          <li class="nav-item text-left">

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
      </div> -->
    </ul>
  </nav>
</template>

<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/inertia-vue3";
import { Link } from "@inertiajs/inertia-vue3";

// const user = computed(() => {
//   return usePage().props.value.auth.user;
// });
</script>

<script>
export default {
  data: () => ({
    perfil: "",
    user_roles: [],
    roles: [],
  }),

  computed: {
    user() {
      return this.$page.props.auth.user;
    },
  },

  mounted() {
    // alert(JSON.stringify(this.user.auth.can['abertura caixa']))
    // this.getUserRoles();
  },

  methods: {
    logout: function () {
      this.loading = true;
      this.axios
        .post("/logout")
        .then((response) => {
          if (response.status === 302 || 401) {
            console.log(error);
            this.loading = false;
          } else {
            window.location.replace("/login");
            // throw error and go to catch block
          }
        })
        .catch((error) => {
          this.loading = false;
          console.log(error);
        });
    },
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


