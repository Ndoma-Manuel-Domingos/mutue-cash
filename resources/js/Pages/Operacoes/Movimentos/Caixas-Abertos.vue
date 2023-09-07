<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h3 class="m-0" >Caixas Abertos referente a data: {{ data_inicio }}</h3>
          </div>
          <div class="col-sm-6">
            <button class="btn btn-dark float-right mr-1" type="button" @click="voltarPaginaAnterior">
              <i class="fas fa-arrow-left"></i> VOLTAR A PÁGINA ANTERIOR
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="container-fluid">
        
            <template v-if="movimentos">
                <div class="row">
                  <div class="col-lg-3 col-6" v-for="movimento in movimentos" :key="movimento.codigo">
                    <div class="small-box bg-info">
                      <div class="inner">
                        <h4 class="text-uppercase text-secondary">operador ACTIVO:</h4>
                        <h5 class="my-3">{{ movimento.operador.nome ?? "" }}</h5>
                        <h4 class="text-uppercase text-secondary">Caixa Aberto por:</h4>
                        <p class="my-3">{{ movimento.operador_created.nome ?? "" }}</p>
                      </div>
                      <div class="icon">
                        <i class="ion ion-bag"></i>
                      </div>
                      <a :href="`/movimentos/fecho-caixa-por-admin?url_caixa_fecho=${movimento.codigo}`" class="small-box-footer" v-if="user.auth.can['fecho caixa']"
                        >Fechar o Caixa <i class="fas fa-arrow-circle-right"></i
                      ></a>
                    </div>
                  </div>
       
                </div>
            </template>
            
            <template v-else>
                <div class="row">
                  <div class="col-12 col-md-12">
                    <div class="alert alert-warning alert-dismissible">
                      <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <h4><i class="icon fas fa-info"></i>Atenção!</h4> 
                        <h5 class="mt-3">Sem Caixas abertos no momentos</h5>
                      </div>
                  </div>
                </div>
            </template>
      </div>
    </div>
  </MainLayouts>
</template>
  
<script setup>
  import { computed } from "vue";
  import { usePage } from "@inertiajs/inertia-vue3";

  const user = computed(() => {
    return usePage().props.value.auth.user;
  });
</script>
  
<script>
import { Link } from "@inertiajs/inertia-vue3";

export default {
  props: ["movimentos"],
  components: {
    Link,
  },
  data() {
    return {
      form: this.$inertia.form({
        operador_id: "",
      }),

      data_inicio: new Date().toISOString().substr(0, 10),
    };
  },
  
    computed: {
      user() {
        return this.$page.props.auth.user;
      },
    },

};
</script>
  
  
  