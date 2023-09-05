<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Dashboard</h1>
          </div>
          <div class="col-sm-3">
            <h1 class="m-0 text-right" v-if="caixa">{{ caixa.code }}</h1>
          </div>
          <div class="col-sm-3">
            <button class="btn btn-dark float-right mr-1" type="button" @click="voltarPaginaAnterior">
              <i class="fas fa-arrow-left"></i> VOLTAR A PÁGINA ANTERIOR
            </button>
          </div>
          <!-- voltarPaginaAnterior() {
            window.history.back();
          }, -->
        </div>
      </div>
    </div>

    <div class="content">
      <div class="container-fluid">
        
        <div class="row">
          <div class="col-12 col-md-12">
            <div class="alert alert-warning alert-dismissible">
              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-info"></i> Atenção!</h5>
                Os registos presentes são diários, caso pretenda ver registos semanal, mensal ou anual, use os filtros que estão no lado direito. 
              </div>
          </div>
        </div>
      
        <div class="row">
          <div class="col-12 col-md-6">
            <div class="row">
              <div class="col-lg-6 col-12 col-md-12">
                <div class="small-box bg-info">
                  <div class="inner">
                    <h4>{{ formatValor(total_depositado ?? 0) }}</h4>
                    <p>Total Valor Depositado.</p>
                  </div>
                  <div class="icon">
                    <i class="ion ion-bag"></i>
                  </div>
                  <Link
                    :href="route('mc.depositos.index')"
                    class="small-box-footer"
                    >Mais detalhe <i class="fas fa-arrow-circle-right"></i
                  ></Link>
                </div>
              </div>

              <div class="col-lg-6 col-12 col-md-12">
                <div class="small-box bg-info">
                  <div class="inner">
                    <h4>{{ formatValor(total_pagamento ?? 0) }}</h4>
                    <p>Total de Pagamentos.</p>
                  </div>
                  <div class="icon">
                    <i class="ion ion-stats-bars"></i>
                  </div>
                  <Link
                    :href="route('mc.pagamentos.index')"
                    class="small-box-footer"
                    >Mais detalhe <i class="fas fa-arrow-circle-right"></i
                  ></Link>
                </div>
              </div>
              
              <div class="col-lg-12 col-12 col-md-12">
                <div class="small-box bg-info">
                  <div class="inner">
                    <h4>{{ formatValor((+total_depositado)+(+total_pagamento)) }}</h4>
                    <p>Total Valor arrecadado.</p>
                  </div>
                  <div class="icon">
                    <i class="ion ion-stats-bars"></i>
                  </div>
                  <Link
                    :href="route('mc.pagamentos.index')"
                    class="small-box-footer"
                    >Mais detalhe <i class="fas fa-arrow-circle-right"></i
                  ></Link>
                </div>
              </div>
              
            </div>
          </div>

          <div class="col-12 col-md-6">
            <div class="row">
              <div class="col-12 col-md-12">
                <form action="" v-if="user.auth.can['relatorio caixa']">
                  <div class="card">
                    <div class="card-header text-info">
                      <i class="fas fa-hand-point-down" style="font-size: 30pt;"></i>
                    </div>
                    <div class="card-body">
                      <div class="row">
                        <div class="form-group col-12 col-md-12">
                          <label class="form-label form-label-sm" for="ano_lectivo">Anos Lectivos</label>
                          <select v-model="ano_lectivo" id="ano_lectivo" class="form-control ">
                            <option :value="ano.Codigo" v-for="ano in ano_lectivos" :key="ano.Codigo">
                            {{ ano.Designacao }}
                            </option>
                          </select>
                        </div>
                        <div class="form-group col-12 col-md-6">
                          <label for="data_inicio" class="form-label-sm">Data Inicio</label>
                          <input type="date" placeholder="DATA INICIO" id="data_inicio" v-model="data_inicio" class="form-control">
                        </div>
                        <div class="form-group col-12 col-md-6">
                          <label for="data_final" class="form-label">Data Final</label>
                          <input type="date" placeholder="DATA FINAL" id="data_final" v-model="data_final" class="form-control">
                        </div>
                      </div>
                    </div>
                
                  </div>
                </form>
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
  props: ["total_depositado", "total_pagamento", "ano_lectivos", "ano_lectivo_activo_id", "caixa"],
  components: {
    Link,
  },
  data() {
    return {
      
      ano_lectivo: this.ano_lectivo_activo_id,
      data_inicio: new Date().toISOString().substr(0, 10),
      data_final: new Date().toISOString().substr(0, 10),
      
      params: {},
      
    };
  },
      
  computed: {
    user() {
      return this.$page.props.auth.user;
    },
  },
  
  watch: {
    options: function (val) {
      this.params.page = val.page;
      this.params.page_size = val.itemsPerPage;
      if (val.sortBy.length != 0) {
        this.params.sort_by = val.sortBy[0];
        this.params.order_by = val.sortDesc[0] ? "desc" : "asc";
      } else {
        this.params.sort_by = null;
        this.params.order_by = null;
      }
      this.updateData();
    },

    ano_lectivo: function (val) {
      this.params.ano_lectivo = val;
      this.updateData();
    },
    data_inicio: function (val) {
      this.params.data_inicio = val;
      this.updateData();
    },
    data_final: function (val) {
      this.params.data_final = val;
      this.updateData();
    },
  },
  methods: {
    updateData() {
      this.$Progress.start();
      this.$inertia.get("/dashboard", this.params, {
        preserveState: true,
        preverseScroll: true,
        onSuccess: () => {
          this.$Progress.finish();
        },
        onError: () => {
          this.$Progress.fail();
        },
      });
    },
    
    somarNumeros(numero1, numero2) {
      // Realiza a soma dos números
      var soma = parseInt(numero1) + parseInt(numero2);
    
      return soma;
    },

    formatValor(atual) {
      const valorFormatado = Intl.NumberFormat("pt-br", {
        style: "currency",
        currency: "AOA",
      }).format(atual);
      return valorFormatado;
    },

    voltarPaginaAnterior() {
      window.history.back();
    },
  },
};
</script>


