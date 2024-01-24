<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h3 class="m-0" v-if="movimento">Movimentos do caixa referente a {{formatarData( data_inicio) }}</h3>
            <h3 class="m-0" v-else>Abertura do caixa referente a {{formatarData( data_inicio) }}</h3>
          </div>
          <div class="col-sm-3">
            <button class="float-right btn-sm btn-primary" v-if="movimento" @click="imprimirComprovativo(movimento)"><i class="fas fa-print"></i> IMPRIMR RELATÓRIO DO CAIXA</button>
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
          
        <div class="row" v-if="$page.props.flash.error">
          <div class="col-12 col-md-12">
            <div class="alert alert-danger alert-dismissible">
              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h2><i class="icon fas fa-info"></i>Atenção!</h2> 
                <h4 class="mt-4">Caro Utilizador, {{ $page.props.flash.error }}</h4>
              </div>
          </div>
        </div>

        <div class="row" v-if="user.auth.can['abertura caixa']">
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
                          :value="item.codigo_importado ?? item.pk_utilizador"
                          v-for="item in utilizadores"
                          :key="item.codigo_importado"
                        >
                          {{ item.nome }} 
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
          
      </div>
    </div>
  </MainLayouts>
</template>
  
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
        operador_id: "",
        // operador_id: this.operador.codigo_importado ?? this.operador.pk_utilizador,
        // valor_inicial: this.ultimo_movimento ? this.ultimo_movimento.valor_arrecadado_total : 0,
        caixa_id: "",
      }),

      data_inicio: new Date().toISOString().substr(0, 10),
      data_final: new Date().toISOString().substr(0, 10),

      params: {},
    };
  },
  
      
  computed: {
    user() {
      return this.$page.props.auth.user;
    },
    utilizadores() {
      const uniqueMap = new Map();
      return this.utilizadores.filter((item) => {
        if (!uniqueMap.has(item.codigo_importado)) {
          uniqueMap.set(item.codigo_importado, true);
          return true;
        }
        return false;
      });
    },
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

      if(this.$page.props.flash.error != null){
        Swal.fire({
          title: "Atenção",
          text: this.$page.props.flash.error,
          icon: "warning",
          confirmButtonColor: "#3d5476",
          confirmButtonText: "Ok",
          onClose: () => {},
        });
        this.$Progress.fail();
        return;
      }

      this.form.valor_inicial = this.removerFormatacaoAOA(this.form.valor_inicial);

      console.log(this.form);

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

    formatarData(valor) {
      let data = new Date(valor);
      if (valor) {
        return (
          (data.getDate() < 10 ? "0" : null) +
          data.getDate() +
          "-" +
          "0" +
          (data.getMonth() + 1) +
          "-" +
          data.getFullYear()
        );
      } else {
        return "00-00-0000";
      }
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

    voltarPaginaAnterior() {
      window.history.back();
    },
  },
};
</script>
  
  
  