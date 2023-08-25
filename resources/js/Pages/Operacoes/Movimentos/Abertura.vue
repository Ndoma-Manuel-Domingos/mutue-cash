<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0" v-if="movimento">Movimentos do Caixa</h1>
            <h1 class="m-0" v-else>Abertura do Caixa</h1>
          </div>
          <div class="col-sm-6">
            <button class="float-right btn-sm btn-primary" v-if="movimento" @click="imprimirComprovativo(movimento)"><i class="fas fa-print"></i> IMPRIMR RELATÓRIO DO CAIXA</button>
          </div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="container-fluid">
        
        <template v-if="user.type_user == 'Administrador'">
          <div class="row" v-if="movimento">
            <div class="col-lg-3 col-6">
              <div class="small-box bg-info">
                <div class="inner">
                  <h4 class="text-uppercase">operador activo</h4>
                  <p>{{ movimento.operador.nome }}</p>
                </div>
                <div class="icon">
                  <i class="ion ion-bag"></i>
                </div>
                <Link :href="route('mc.depositos.index')" class="small-box-footer"
                  >Mais detalhe <i class="fas fa-arrow-circle-right"></i
                ></Link>
              </div>
            </div>
            
            
            <div class="col-lg-3 col-6">
              <div class="small-box bg-info">
                <div class="inner">
                  <h4 class="text-uppercase">{{ movimento.caixa.nome ?? '' }}</h4>
                  <p class="text-uppercase">{{ movimento.status ?? '' }}</p>
                </div>
                <div class="icon">
                  <i class="ion ion-bag"></i>
                </div>
                <Link :href="route('mc.depositos.index')" class="small-box-footer"
                  >Mais detalhe <i class="fas fa-arrow-circle-right"></i
                ></Link>
              </div>
            </div>
  
            <div class="col-lg-3 col-6">
              <div class="small-box bg-info">
                <div class="inner">
                  <h4 class="text-uppercase">Valor de Abertura</h4>
                  <p>{{ formatValor(movimento.valor_abertura) }}</p>
                </div>
                <div class="icon">
                  <i class="ion ion-bag"></i>
                </div>
                <Link :href="route('mc.depositos.index')" class="small-box-footer"
                  >Mais detalhe <i class="fas fa-arrow-circle-right"></i
                ></Link>
              </div>
            </div>
  
            <div class="col-lg-3 col-6">
              <div class="small-box bg-info">
                <div class="inner">
                  <h4 class="text-uppercase">Total de Depósitos</h4>
                  <p>{{ formatValor(movimento.valor_arrecadado_depositos) }}</p>
                </div>
                <div class="icon">
                  <i class="ion ion-bag"></i>
                </div>
                <Link :href="route('mc.depositos.index')" class="small-box-footer"
                  >Mais detalhe <i class="fas fa-arrow-circle-right"></i
                ></Link>
              </div>
            </div>
  
            <div class="col-lg-3 col-6">
              <div class="small-box bg-info">
                <div class="inner">
                  <h4 class="text-uppercase">Total de Pag. Facturado</h4>
                  <p>{{ formatValor(movimento.valor_facturado_pagamento) }}</p>
                </div>
                <div class="icon">
                  <i class="ion ion-bag"></i>
                </div>
                <Link :href="route('mc.depositos.index')" class="small-box-footer"
                  >Mais detalhe <i class="fas fa-arrow-circle-right"></i
                ></Link>
              </div>
            </div>
            
            <!-- <div class="col-lg-3 col-6">
              <div class="small-box bg-info">
                <div class="inner">
                  <h4 class="text-uppercase">Total de Pag. Recebido</h4>
                  <p>{{ formatValor(movimento.valor_arrecadado_pagamento) }}</p>
                </div>
                <div class="icon">
                  <i class="ion ion-bag"></i>
                </div>
                <Link :href="route('mc.depositos.index')" class="small-box-footer"
                  >Mais detalhe <i class="fas fa-arrow-circle-right"></i
                ></Link>
              </div>
            </div> -->
  
            <div class="col-lg-3 col-6">
              <div class="small-box bg-info">
                <div class="inner">
                  <h4 class="text-uppercase">Total arrecadado</h4>
                  <p>{{ formatValor(movimento.valor_arrecadado_total) }}</p>
                </div>
                <div class="icon">
                  <i class="ion ion-bag"></i>
                </div>
                <Link :href="route('mc.depositos.index')" class="small-box-footer"
                  >Mais detalhe <i class="fas fa-arrow-circle-right"></i
                ></Link>
              </div>
            </div>
  
          </div>
  
          <div class="row" v-else>
            <div class="col-12 col-md-6">
              <form action="" @submit.prevent="submit">
                <div class="card">
                  <div class="card-body">
                    <div class="row">
                      <div class="form-group col-12 col-md-12">
                        <label for="valor_inicial" class="form-label"
                          >VALOR INICIAL</label
                        >
                        <input
                          type="text"
                          placeholder="VALOR INICIAL"
                          id="valor_inicial"
                          v-model="form.valor_inicial"
                          @keyup="formatarMoeda()"
                          class="form-control"
                        />
                        <div class="p-0" v-if="form.errors.valor_inicial">
                          <p class="text-danger">
                            {{ form.errors.valor_inicial }}
                          </p>
                        </div>
                      </div>
  
                      <div class="form-group col-12 col-md-12">
                        <label for="caixa_id" class="form-label">CAIXA</label>
                        <select
                          v-model="form.caixa_id"
                          id="caixa_id"
                          class="form-control"
                        >
                          <option value="">Selecione</option>
                          <option
                            :value="caixa.codigo"
                            v-for="caixa in caixas"
                            :key="caixa.codigo"
                          >
                            {{ caixa.nome }}
                          </option>
                        </select>
                        <div class="p-0" v-if="form.errors.caixa_id">
                          <p class="text-danger">{{ form.errors.caixa_id }}</p>
                        </div>
                      </div>
                      
                      <div class="form-group col-12 col-md-12">
                        <label for="caixa_id" class="form-label">OPERADOR(A)</label>
                        <select
                          v-model="form.operador_id"
                          id="operador_id"
                          class="form-control"
                        >
                          <option value="">Selecione</option>
                          <option
                            :value="item.utilizadores.codigo_importado"
                            v-for="item in utilizadores"
                            :key="item.utilizadores.codigo_importado"
                          >
                            {{ item.utilizadores.nome }}
                          </option>
                        </select>
                        <div class="p-0" v-if="form.errors.operador_id">
                          <p class="text-danger">{{ form.errors.operador_id }}</p>
                        </div>
                      </div>
                      
                    </div>
                  </div>
                  <div class="card-footer">
                    <button type="submit" class="btn-sm btn-info">Abrir</button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </template>
        
        <template v-else>
          <div class="row" v-if="movimento">
            <div class="col-lg-3 col-6">
              <div class="small-box bg-info">
                <div class="inner">
                  <h4 class="text-uppercase">operador activo</h4>
                  <p>{{ movimento.operador.nome }}</p>
                </div>
                <div class="icon">
                  <i class="ion ion-bag"></i>
                </div>
                <Link :href="route('mc.depositos.index')" class="small-box-footer"
                  >Mais detalhe <i class="fas fa-arrow-circle-right"></i
                ></Link>
              </div>
            </div>
            
            
            <div class="col-lg-3 col-6">
              <div class="small-box bg-info">
                <div class="inner">
                  <h4 class="text-uppercase">{{ movimento.caixa.nome ?? '' }}</h4>
                  <p class="text-uppercase">{{ movimento.status ?? '' }}</p>
                </div>
                <div class="icon">
                  <i class="ion ion-bag"></i>
                </div>
                <Link :href="route('mc.depositos.index')" class="small-box-footer"
                  >Mais detalhe <i class="fas fa-arrow-circle-right"></i
                ></Link>
              </div>
            </div>
  
            <div class="col-lg-3 col-6">
              <div class="small-box bg-info">
                <div class="inner">
                  <h4 class="text-uppercase">Valor de Abertura</h4>
                  <p>{{ formatValor(movimento.valor_abertura) }}</p>
                </div>
                <div class="icon">
                  <i class="ion ion-bag"></i>
                </div>
                <Link :href="route('mc.depositos.index')" class="small-box-footer"
                  >Mais detalhe <i class="fas fa-arrow-circle-right"></i
                ></Link>
              </div>
            </div>
  
            <div class="col-lg-3 col-6">
              <div class="small-box bg-info">
                <div class="inner">
                  <h4 class="text-uppercase">Total de Depósitos</h4>
                  <p>{{ formatValor(movimento.valor_arrecadado_depositos) }}</p>
                </div>
                <div class="icon">
                  <i class="ion ion-bag"></i>
                </div>
                <Link :href="route('mc.depositos.index')" class="small-box-footer"
                  >Mais detalhe <i class="fas fa-arrow-circle-right"></i
                ></Link>
              </div>
            </div>
  
            <div class="col-lg-3 col-6">
              <div class="small-box bg-info">
                <div class="inner">
                  <h4 class="text-uppercase">Total de Pag. Facturado</h4>
                  <p>{{ formatValor(movimento.valor_facturado_pagamento) }}</p>
                </div>
                <div class="icon">
                  <i class="ion ion-bag"></i>
                </div>
                <Link :href="route('mc.depositos.index')" class="small-box-footer"
                  >Mais detalhe <i class="fas fa-arrow-circle-right"></i
                ></Link>
              </div>
            </div>

            <div class="col-lg-3 col-6">
              <div class="small-box bg-info">
                <div class="inner">
                  <h4 class="text-uppercase">Total arrecadado</h4>
                  <p>{{ formatValor(movimento.valor_arrecadado_total) }}</p>
                </div>
                <div class="icon">
                  <i class="ion ion-bag"></i>
                </div>
                <Link :href="route('mc.depositos.index')" class="small-box-footer"
                  >Mais detalhe <i class="fas fa-arrow-circle-right"></i
                ></Link>
              </div>
            </div>
  
          </div>
          
          
          <div class="row" v-else>
            <div class="col-12 col-md-12">
              <div class="alert alert-warning alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                  <h2><i class="icon fas fa-info"></i>Atenção!</h2> 
                  <h4 class="mt-4">Operador(a) {{ user.nome }}, Infelizmente ainda não tens acesso a um caixa por favor, entra em contacto com administrador do sistema ou com o gestor(a) do caixa!</h4>
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
  props: ["caixas", "movimento", "ultimo_movimento", "utilizadores", "operador"],
  components: {
    Link,
  },
  data() {
    return {
      form: this.$inertia.form({
        valor_inicial: 0,
        operador_id: this.operador.codigo_importado,
        // valor_inicial: this.ultimo_movimento ? this.ultimo_movimento.valor_arrecadado_total : 0,
        caixa_id: "",
      }),

      params: {},
    };
  },
  mounted(){
    this.form.valor_inicial = this.formatValor(this.form.valor_inicial)
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
  },
  methods: {
    verificarLetras() {
      // Expressão regular que verifica se a string contém letras (a-zA-Z)
      const regexLetras = /[a-zA-Z]/;
      this.contemLetras = regexLetras.test(this.form.valor_inicial);
    },

    async submit() {
      this.$Progress.start();

      if (this.removerFormatacaoAOA(this.form.valor_inicial) < -1) {
        Swal.fire({
          title: "Atenção",
          text: "O valor da abertura Invalido",
          icon: "warning",
          confirmButtonColor: "#3d5476",
          confirmButtonText: "Ok",
          onClose: () => {},
        });
        this.$Progress.fail();
        return;
      }

      this.form.valor_inicial = this.removerFormatacaoAOA(this.form.valor_inicial);

      this.form.post("/movimentos/abertura-caixa", {
        preverseScroll: true,
        onSuccess: () => {
          this.form.reset();
          this.$Progress.finish();

          Swal.fire({
            title: "Bom Trabalho",
            text: "Abertura do caixa realizado com sucesso!",
            icon: "success",
            confirmButtonColor: "#3d5476",
            confirmButtonText: "Ok",
            onClose: () => {},
          });
          this.form.valor_inicial = this.formatValor(this.form.valor_inicial)
        },
        onError: (errors) => {
          this.form.valor_inicial = this.formatValor(this.form.valor_inicial)
          console.log(errors);
          this.$Progress.fail();
        },
      });
    },
    
    formatarMoeda() {
      // Remover caracteres que não são números
      let valor = this.form.valor_inicial.replace(/\D/g, '');

      // Converter o valor para número
      valor = Number(valor) / 100; // Dividir por 100 para ter o valor em reais

      // Formatar o número para moeda
      this.form.valor_inicial = valor.toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'AOA'
      });
    },
    
    removerFormatacaoAOA(valor) {
      // Remover caracteres não numéricos, exceto a vírgula
      const valorNumerico = valor.replace(/[^\d,]/g, '');
    
      // Remover vírgulas repetidas, mantendo apenas uma
      const valorSemVirgulasRepetidas = valorNumerico.replace(/(,)\1+/g, ',');
    
      // Substituir a vírgula por ponto decimal para obter o valor numérico
      const valorNumericoFinal = valorSemVirgulasRepetidas.replace(/,/g, '.');
    
      return valorNumericoFinal;
    },
    
    
    imprimirComprovativo(item) 
    {
      window.open(`/movimentos/imprimir-comprovativo?codigo=${item.codigo}`, "_blank");
    },

    formatValor(atual) {
      const valorFormatado = Intl.NumberFormat("pt-br", {
        style: "currency",
        currency: "AOA",
      }).format(atual);
      return valorFormatado;
    },
  },
};
</script>
  
  
  