<template>
    <MainLayouts>
      <div class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1 class="m-0">Registrar Saídas de Valores do Caixa</h1>
            </div>
            <div class="col-sm-6">
              <button
                class="btn btn-dark float-right mr-1"
                type="button"
                @click="voltarPaginaAnterior"
              >
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
            <div class="col-12 col-md-8">
              <form action="" @submit.prevent="submit">
                <div class="card">
                  <div class="card-body">
                  
                    <div class="row">
                      <div class="form-group col-12 col-md-6">
                        <label for="valor_abertura" class="form-label"
                          >VALOR DE ABERTURA</label
                        >
                        <input
                            disabled
                            type="text"
                            placeholder="TOTAL VALOR ABERTURA"
                            id="valor_abertura"
                            v-model="form.valor_abertura"
                            class="form-control"
                            @keyup="formatarMoeda()"
                        />
                        <div class="p-0" v-if="form.errors.valor_abertura">
                          <p class="text-danger">
                            {{ form.errors.valor_abertura }}
                          </p>
                        </div>
                      </div>
  
                      <div class="form-group col-12 col-md-6">
                        <label for="valor_depositado" class="form-label"
                          >TOTAL DE DEPÓSITOS</label
                        >
                        <input
                        disabled
                          type="text"
                          placeholder="TOTAL DE DEPÓSITOS"
                          id="valor_depositado"
                          v-model="form.valor_depositado"
                          @keyup="formatarMoeda()"
                          class="form-control"
                        />
                        <div class="p-0" v-if="form.errors.valor_depositado">
                          <p class="text-danger">
                            {{ form.errors.valor_depositado }}
                          </p>
                        </div>
                      </div>
  
                      <div class="form-group col-12 col-md-3">
                        <label for="valor_pagamento" class="form-label"
                          >TOTAL DE PAGAMENTOS</label
                        >
                        <input
                        disabled
                          type="text"
                          placeholder="TOTAL DE PAGAMENTOS"
                          id="valor_pagamento"
                          v-model="form.valor_pagamento"
                          class="form-control"
                          @keyup="formatarMoeda()"
                        />
                        <div class="p-0" v-if="form.errors.valor_pagamento">
                          <p class="text-danger">
                            {{ form.errors.valor_pagamento }}
                          </p>
                        </div>
                      </div>
  
                      <div class="form-group col-12 col-md-3">
                        <label for="valor_facturado" class="form-label"
                          >VALOR FACTURADO</label
                        >
                        <input
                        disabled
                          type="text"
                          placeholder="TOTAL DE PAGAMENTOS"
                          id="valor_facturado"
                          v-model="form.valor_facturado"
                          class="form-control"
                          @keyup="formatarMoeda()"
                        />
                        <div class="p-0" v-if="form.errors.valor_facturado">
                          <p class="text-danger">
                            {{ form.errors.valor_facturado }}
                          </p>
                        </div>
                      </div>
  
                      <div class="form-group col-12 col-md-3">
                        <label for="operador_id" class="form-label"
                          >OPERADOR</label
                        >
                        <select
                        disabled
                          v-model="form.operador_id"
                          id="operador_id"
                          class="form-control"
                        >
                          <option
                            :value="operador.codigo_importado"
                            :key="operador.codigo_importado"
                            selected="selected"
                          >
                            {{ operador.nome }}
                          </option>
                        </select>
                        <div class="p-0" v-if="form.errors.operador_id">
                          <p class="text-danger">{{ form.errors.operador_id }}</p>
                        </div>
                      </div>
  
                      <div class="form-group col-12 col-md-3">
                        <label for="caixa_id" class="form-label">CAIXA</label>
                        <select
                        disabled
                          v-model="form.caixa_id"
                          id="caixa_id"
                          class="form-control"
                        >
                          <option
                            :value="caixa ? caixa.codigo : ''"
                            selected="selected"
                          >
                            {{ caixa ? caixa.nome : "" }}
                          </option>
                        </select>
                        <div class="p-0" v-if="form.errors.caixa_id">
                          <p class="text-danger">{{ form.errors.caixa_id }}</p>
                        </div>
                      </div>
                      
                      <div class="form-group col-12 col-md-6">
                        <label for="valor_total_arracadado" class="form-label">VALOR TOTAL ARRECADADO</label>
                        <input
                            disabled
                            type="text"
                            placeholder="VALOR TOTAL ARRECADADO"
                            id="valor_total_arracadado"
                            v-model="form.valor_total_arracadado"
                            @keyup="formatarMoeda()"
                            class="form-control"
                        />
                       
                      </div>
                      
                      <div class="form-group col-12 col-md-6">
                        <label for="valor_a_retirar" class="form-label">VALOR A RETIRAR <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            placeholder="VALOR A RETIRAR"
                            id="valor_a_retirar"
                            v-model="form.valor_a_retirar"
                            @keyup="formatarMoeda()"
                            @input="calcular_valor_restante"
                            class="form-control"
                        />
                       
                      </div>
  
                      <div class="form-group col-12 col-md-12">
                        <label for="observacao" class="form-label"
                          >OBSERVAÇÃO <span class="text-danger">*</span></label
                        >
                        <textarea
                          id="observacao"
                          v-model="form.observacao"
                          rows="3"
                          class="form-control"
                          placeholder="OBSERVAÇÃO:"
                        ></textarea>
                        <div class="p-0" v-if="form.errors.caixa_id">
                          <p class="text-danger">{{ form.errors.caixa_id }}</p>
                        </div>
                      </div>
                      
                      <div class="form-group col-12 col-md-6">
                        <label for="observacao" class="form-label"
                          >VALOR RESTANTE <span class="text-danger">*</span></label
                        >
                        <input
                          disabled
                          id="observacao"
                          v-model="valor_restante"
                          class="form-control"
                          placeholder="VALOR RESTANTE:"
                        />
                      </div>
                    </div>
                  </div>
                  <div class="card-footer">
                    <button
                      type="submit"
                      class="btn-sm btn-info"
                      v-show="caixa"
                      :disabled="isfechado"
                    >
                      Fechar o Caixa
                    </button>
                    <Link
                      v-if="movimento"
                      @click="imprimirComprovativo(movimento)"
                      class="float-right btn-sm btn-primary"
                      ><i class="fas fa-print"></i> Imprimr</Link
                    >
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
    props: ["caixa", "operador", "movimento", "utilizadores"],
    components: {
      Link,
    },
  
    data() {
      return {
        text_input: "",
        form: this.$inertia.form({
          valor_depositado: this.movimento
            ? this.movimento.valor_arrecadado_depositos
            : "",
          valor_pagamento: this.movimento
            ? this.movimento.valor_arrecadado_pagamento
            : "",
          valor_facturado: this.movimento
            ? this.movimento.valor_facturado_pagamento
            : "",
          valor_abertura: this.movimento ? this.movimento.valor_abertura : "",
          caixa_id: this.caixa ? this.caixa.codigo : "",
          operador_id: this.operador.codigo_importado ?? "",
          movimento_id: this.movimento ? this.movimento.codigo : "",
          observacao: "",
          valor_total_arracadado: this.movimento
            ? this.movimento.valor_arrecadado_total
            : "",   
          valor_a_retirar: "",
           
        }),
        
        valor_restante: 0, 
  
        isUpdate: false,
        isfechado: false,
  
        params: {},
      };
    },
  
    mounted() {
      this.form.valor_abertura = this.formatValor(this.form.valor_abertura);
      this.form.valor_pagamento = this.formatValor(this.form.valor_pagamento);
      this.form.valor_facturado = this.formatValor(this.form.valor_facturado);
      this.form.valor_depositado = this.formatValor(this.form.valor_depositado);
      this.form.valor_total_arracadado = this.formatValor(this.form.valor_total_arracadado);
      this.valor_restante = this.formatValor(this.valor_restante);
      this.isfechado = false;
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
      
      calcular_valor_restante(){
        let result = parseItn(this.removerFormatacaoAOA(this.form.valor_total_arracadado)) -  parseInt(this.removerFormatacaoAOA(this.form.valor_a_retirar));
        this.valor_restante = result;
        
         /*let valor_restante = this.result.replace(/\D/g, "");
         valor_restante = Number(valor_restante) / 100; // Dividir por 100 para ter o valor em reais
         this.valor_restante = valor_restante.toLocaleString("pt-BR", {
          style: "currency",
          currency: "AOA",
        });*/
      },
  
      formatarMoeda() {
        // Remover caracteres que não são números
        let valor_total_arracadado = this.form.valor_total_arracadado.replace(/\D/g, "");
        let valor_a_retirar = this.form.valor_a_retirar.replace(/\D/g, "");
    
        // Converter o valor para número
        valor_total_arracadado = Number(valor_total_arracadado) / 100; // Dividir por 100 para ter o valor em reais
        valor_a_retirar = Number(valor_a_retirar) / 100; // Dividir por 100 para ter o valor em reais
 
        // Formatar o número para moeda
        this.form.valor_total_arracadado = valor_total_arracadado.toLocaleString("pt-BR", {
          style: "currency",
          currency: "AOA",
        });
        
        this.form.valor_a_retirar = valor_a_retirar.toLocaleString("pt-BR", {
          style: "currency",
          currency: "AOA",
        });

      },
  
      removerFormatacaoAOA(valor) {
        // Remover caracteres não numéricos, exceto a vírgula
        const valorNumerico = valor.replace(/[^\d,]/g, "");
  
        // Remover vírgulas repetidas, mantendo apenas uma
        const valorSemVirgulasRepetidas = valorNumerico.replace(/(,)\1+/g, ",");
  
        // Substituir a vírgula por ponto decimal para obter o valor numérico
        const valorNumericoFinal = valorSemVirgulasRepetidas.replace(/,/g, ".");
  
        return valorNumericoFinal;
      },
  
      async submit() {
        this.$Progress.start();
  
        if (this.form.valor_abertura === "") {
          Swal.fire({
            title: "Atenção",
            text: "Valor de Abertura invalido!",
            icon: "warning",
            confirmButtonColor: "#3d5476",
            confirmButtonText: "Ok",
            onClose: () => {},
          });
          this.$Progress.fail();
          return;
        }
  
        if (this.form.valor_depositado === "") {
          Swal.fire({
            title: "Atenção",
            text: "Total Valor arrecadado em depositos invalido!",
            icon: "warning",
            confirmButtonColor: "#3d5476",
            confirmButtonText: "Ok",
            onClose: () => {},
          });
          this.$Progress.fail();
          return;
        }
  
        if (this.form.valor_pagamento === "") {
          Swal.fire({
            title: "Atenção",
            text: "Total Valor arrecadado em pagamentos invalido!",
            icon: "warning",
            confirmButtonColor: "#3d5476",
            confirmButtonText: "Ok",
            onClose: () => {},
          });
          this.$Progress.fail();
          return;
        }
  
        if (
          this.removerFormatacaoAOA(this.form.valor_abertura) !=
          this.movimento.valor_abertura
        ) {
          Swal.fire({
            title: "Atenção",
            text: "O total valor abertura e o valor informe em abertura não conferem!",
            icon: "warning",
            confirmButtonColor: "#3d5476",
            confirmButtonText: "Ok",
            onClose: () => {},
          });
          this.$Progress.fail();
          return;
        }
  
        if (
          this.removerFormatacaoAOA(this.form.valor_depositado) !=
          this.movimento.valor_arrecadado_depositos
        ) {
          Swal.fire({
            title: "Atenção",
            text: "O total valor arrecadado em depositos e o valor informe em depositos não conferem!",
            icon: "warning",
            confirmButtonColor: "#3d5476",
            confirmButtonText: "Ok",
            onClose: () => {},
          });
          this.$Progress.fail();
          return;
        }
  
        if (
          this.removerFormatacaoAOA(this.form.valor_pagamento) !=
          this.movimento.valor_arrecadado_pagamento
        ) {
          Swal.fire({
            title: "Atenção",
            text: "O total valor arrecadado em pagamentos e o valor informe em pagamentos não conferem!",
            icon: "warning",
            confirmButtonColor: "#3d5476",
            confirmButtonText: "Ok",
            onClose: () => {},
          });
          this.$Progress.fail();
          return;
        }
  
        if (this.isUpdate) {
        } else {
          try {
            // Faça uma requisição POST para o backend Laravel
            // this.form.valor_abertura = this.removerFormatacaoAOA(this.form.valor_abertura);
            // this.form.valor_pagamento = this.removerFormatacaoAOA(this.form.valor_pagamento);
            // this.form.valor_facturado = this.removerFormatacaoAOA(this.form.valor_facturado);
            // this.form.valor_depositado = this.removerFormatacaoAOA(this.form.valor_depositado);
            const response = await axios.post(
              "/movimentos/fecho-caixa",
              this.form
            );
  
            // A resposta do Laravel estará disponível em response.data
            this.form.reset();
            this.$Progress.finish();
            Swal.fire({
              title: "Bom Trabalho",
              text: response.data.message,
              icon: "success",
              confirmButtonColor: "#3d5476",
              confirmButtonText: "Ok",
              onClose: () => {},
            });
  
            this.isfechado = true;
            this.imprimirComprovativo(response.data.data);
  
            // Faça algo com a resposta, se necessário
          } catch (error) {
            // Lide com erros, se houver
            console.error(error);
            //   sweetError("Não foi possível fazer o deposito!");
            this.$Progress.fail();
          }
        }
      },
  
      imprimirComprovativo(item) {
        window.open(
          `/movimentos/imprimir-comprovativo?codigo=${item.codigo}`,
          "_blank"
        );
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
      
      
      