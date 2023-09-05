<template>
  <div class="hold-transition sidebar-mini">
    <div class="wrapper">
      <vue-progress-bar></vue-progress-bar>

      <nav class="main-header class= navbar navbar-expand navbar-danger navbar-light" style="background: linear-gradient(to right, #55c9f2, #0c9979);">
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
          </li>
        </ul>

        <div class="ml-auto">

          <ul class="navbar-nav">
          
          <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
              <i class="far fa-bell"></i>
              <span class="badge badge-danger navbar-badge">2</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
            <span class="dropdown-header">2 Notifications</span>
            <div class="dropdown-divider"></div>
            
            <template v-for="notification in this.$page.props.auth.notifications" :key="notification.id">
              <a :href="route('notifications.show', notification.id)" class="dropdown-item">
                <i class="fas fa-envelope mr-2"></i> {{ notification.data.title }}
                <span class="float-right text-muted text-sm">{{ notification.data.created_at }}</span>
              </a>
            </template>
          
            <div class="dropdown-divider"></div>
              <a :href="route('notifications.index')" class="dropdown-item dropdown-footer">Todas notificações</a>
            </div>
          </li>
            
            <li class="nav-item">
              <!-- href="/logout"
                method="post" -->
              <Link
                class="nav-link btn btn-link text-white"
                as="button"
                href="/movimentos/bloquear-caixa"
                type="button"
              >
                <i class="fas fa-box"></i>
                Bloquear Caixa
              </Link>
            </li>
          
            <li class="nav-item">
              <!-- href="/logout"
                method="post" -->
              <Link
                class="nav-link btn btn-link text-danger"
                as="button"
                type="button"
                @click="logout"
              >
                <i class="fas fa-sign-out-alt"></i>
              </Link>
            </li>
          </ul>
        </div>
      </nav>

      <aside class="main-sidebar sidebar-secondary elevation-4">
        <Link :href="route('mc.dashboard')" class="brand-link text-center" style="border-bottom: 1px solid #028bbf;">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-info" style="height: 50px;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
          </svg>

          <h5 class="text-info">MUTUE CASH</h5>
        </Link>

        <div class="sidebar">
          <div class="user-panel mt-3 d-flex">
            <div class="image">
              <img
                src="~admin-lte/dist/img/user2-160x160.jpg"
                class="img-circle elevation-2"
                alt="User Image"
              />
            </div>
            <div class="info">
              <a href="#" class="d-block pb-1">{{ user.nome }}</a>
              <h6 v-for="perfil in user.perfils" :key="perfil">
                <!-- {{ user.type_user ==  'Administrador' ? 'Admininistrador/Financeiro' : user.type_user }}  -->
                {{ perfil.name }}
              </h6>
            </div>
        </div>

          <Menu />

        </div>
      </aside>

      <div class="content-wrapper">
        <slot />
      </div>
      <!-- <Footer /> -->
    </div>
  </div>

</template>

<script setup>
  import { computed } from "vue";
  import Menu from "./Partials/Menu.vue";
  import { usePage } from "@inertiajs/inertia-vue3";
  import { Link } from "@inertiajs/inertia-vue3";

  const user = computed(() => {
    return usePage().props.value.auth.user;
  });
</script>

<script>
  import { sweetSuccess, sweetError } from "../../components/Alert";

  export default {
    data() {
      return {
        result: [],
      };
    },

    methods: {
      
    
      logout(){
        axios
          .post("/logout")
          .then((response) => {
          
              if(response.data.status == 201){
                Swal.fire({
                  icon: "warning",
                  title: "Atenção",
                  text: response.data.message,
                });
              }else {
                Swal.fire({
                  icon: "success",
                  title: "Sucesso!",
                  text: "Conta encerrada com sucesso!",
                });
              }
              
              window.location.reload();
          })
          .catch((error) => {
            console.error(error);
          });
      },
      
      sair_definitivamente(){
      
        axios
          .post("/logout")
          .then((response) => {
          
              if(response.data.status == 201){
                Swal.fire({
                  icon: "warning",
                  title: "Atenção",
                  text: response.data.message,
                });
              }else {
                Swal.fire({
                  icon: "success",
                  title: "Sucesso!",
                  text: "Conta encerrada com sucesso!",
                });
              }
              
              window.location.reload();
          })
          .catch((error) => {
            console.error(error);
          });
      },
      
      bloaquearCaixa()
      {
        axios
          .get("/movimentos/bloquear-caixa")
          .then((response) => {
              // Swal.fire({
              //   icon: "warning",
              //   title: "Atenção...",
              //   text: response.data.message,
              // });
          })
          .catch((error) => {
            console.error(error);
          });
      }
    },
  };
</script>
