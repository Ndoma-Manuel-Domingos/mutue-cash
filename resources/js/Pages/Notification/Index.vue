<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Notificações</h1>
            </div>
          <div class="col-sm-3">
            <button class="btn btn-dark float-right mr-1" type="button" @click="voltarPaginaAnterior">
              <i class="fas fa-arrow-left"></i> VOLTAR A PÁGINA ANTERIOR
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="container-fluid">
        <div class="row">
           <div class="col-md-12 col-12">
                <div class="card card-default">
                    <div class="card-header">
                      <h3 class="card-title">
                      <i class="fas fa-bullhorn"></i>
                      </h3>
                    </div>
                    
                    <div class="card-body">
                      <div class="callout callout-info" v-for="notification in notifications" :key="notification.id">
                        <h5>{{ notification.data.title }}</h5>
                        <p>{{ notification.data.description }}</p>
                        
                        <div class="mt-3">
                            <a :href="route('notifications.show', notification.id)" class="btn btn-sm btn-primary mr-2 text-decoration-none text-white">Acessar</a>
                            <!--<a :href="route('notificatiions.show', $notification.id)" @click="destroy(notification.id)" class="btn btn-sm btn-danger mr-2">Eliminar</a> -->
                        </div>
                      </div>
                     
                    </div>
                </div>
            </div>
        </div>
      </div>
    </div>
    
  </MainLayouts>
</template>

<script>
import { Link } from "@inertiajs/inertia-vue3";
export default {
  props: [],
  components: {
    Link,
  },
  data() {
    return {      
    };
  },
      
  computed: {
    user() {
      return this.$page.props.auth.user;
    },
    notifications() {
      return this.$page.props.auth.notifications;
    },
  },
  
  watch: {

  },
  
  methods: {
  
    destroy(notification) {
      this.form.id = notification;
      this.form.delete(route("notification.destroy", notification), {
        onSuccess: () => {
          this.$inertia.get(route("notification.index"), this.params, {
            onSuccess: () => {}
          });
        }
      });
    },
    
    goToPage(page) {
      this.$inertia.visit(this.route(page));
    },
    
    goToPageNotification(notification) {
      this.formNotification.get(route("notification.show", notification.id), {
        onSuccess: () => {
          if (notification.data.parameter) {
            this.$inertia.visit(
              this.route(notification.data.route, notification.data.parameter)
            );
          } else {
            this.$inertia.visit(this.route(notification.data.route));
          }
        }
      });
    },

    voltarPaginaAnterior() {
      window.history.back();
    },
  },
};
</script>