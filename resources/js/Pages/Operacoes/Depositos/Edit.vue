<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-8">
            <h1 class="m-0">Editar Deposito</h1>
          </div>
          <div class="col-sm-4">
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
          <form action="" @submit.prevent="submit">
            <div class="col-12 col-md-12">
              <div class="card">
                <div class="card-body">
                  <div class="modal-body py-3">
                    <div class="row">
                      <div class="col-12 col-md-12 mb-3">
                        <div class="col-12 col-md-4">
                          <div class="form-group">
                            <label for="" class="form-label">Matricula</label>
                            <div class="input-group">
                              <input
                                class="form-control"
                                v-model="form.codigo_matricula"
                                disabled
                                type="search"
                                placeholder="Introduz o Número da matricula!"
                                aria-label="Search"
                              />
                            </div>
                            <div
                              v-if="form.errors.codigo_matricula"
                              class="text-danger"
                            >
                              {{ form.errors.codigo_matricula }}
                            </div>
                          </div>
                        </div>
                      </div>

                      <div class="col-12 col-md-4 mb-3">
                        <div class="form-group">
                          <label for="" class="form-label">Nome Completo</label>
                          <input
                            type="text"
                            v-model="form.nome_estudante"
                            disabled
                            class="form-control"
                            placeholder="Nome Completo"
                          />
                        </div>
                      </div>

                      <div class="col-12 col-md-4 mb-3">
                        <div class="form-group">
                          <label for="" class="form-label">Número do BI</label>
                          <input
                            type="text"
                            v-model="form.bilheite_estudante"
                            disabled
                            class="form-control"
                            placeholder="Número do BI"
                          />
                        </div>
                      </div>

                      <div class="col-12 col-md-4 mb-3">
                        <div class="form-group">
                          <label for="" class="form-label">Curso</label>
                          <input
                            type="text"
                            v-model="form.curso_estudante"
                            disabled
                            class="form-control"
                            placeholder="Curso"
                          />
                        </div>
                      </div>

                      <div class="col-12 col-md-4 mb-3">
                        <div class="form-group">
                          <label for="" class="form-label"
                            >Valor a depositar</label
                          >
                          <div class="input-group">
                            <input
                              type="text"
                              v-model="form.valor_a_depositar"
                              class="form-control"
                              placeholder="informe o valor a depositar"
                              @keyup="formatarMoeda()"
                            />
                            <div class="input-group-append">
                              <button type="button" class="btn btn-info">
                                kz
                              </button>
                            </div>
                          </div>
                          <div
                            v-if="form.errors.valor_a_depositar"
                            class="text-danger"
                          >
                            {{ form.errors.valor_a_depositar }}
                          </div>
                        </div>
                      </div>

                      <div class="col-12 col-md-6"></div>

                      <div class="col-12 col-md-1 mb-3 text-center">
                        <div class="form-group">
                          <label for="" class="form-label">A4</label>
                          <div class="input-group">
                            <input
                              type="radio"
                              selected
                              v-model="form.factura"
                              value="A4"
                              class="form-control"
                            />
                          </div>
                        </div>
                      </div>

                      <div class="col-12 col-md-1 mb-3 text-center">
                        <div class="form-group">
                          <label for="" class="form-label">TICKET</label>
                          <div class="input-group">
                            <input
                              type="radio"
                              value="Ticket"
                              v-model="form.factura"
                              class="form-control"
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                      Salvar
                    </button>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </MainLayouts>
</template>
    
<script>
import { sweetSuccess, sweetError } from "../../../components/Alert";
import Paginacao from "../../../Shared/Paginacao";
import { Link } from "@inertiajs/inertia-vue3";

export default {
  props: ["deposito", "preinscricao"],
  components: { Link, Paginacao },

  computed: {
    user() {
      return this.$page.props.auth.user;
    },
  },

  mounted() {
    this.form.valor_a_depositar = this.formatValor(this.form.valor_a_depositar);
  },

  data() {
    return {
      form: this.$inertia.form({
        codigo: this.deposito.codigo,
        codigo_matricula: this.deposito.codigo_matricula_id,
        valor_a_depositar: this.deposito.valor_depositar,
        nome_estudante: this.preinscricao.Nome_Completo,
        bilheite_estudante: this.preinscricao.Bilhete_Identidade ?? "",
        curso_estudante: this.preinscricao.Designacao ?? "",
        factura: this.deposito.tipo_folha,
      }),
    };
  },

  methods: {
    formatarMoeda() {
      // Remover caracteres que não são números
      let valor = this.form.valor_a_depositar.replace(/\D/g, "");

      // Converter o valor para número
      valor = Number(valor) / 100; // Dividir por 100 para ter o valor em reais

      // Formatar o número para moeda
      this.form.valor_a_depositar = valor.toLocaleString("pt-BR", {
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

      if (this.removerFormatacaoAOA(this.form.valor_a_depositar) < 10) {
        Swal.fire({
          title: "Atenção",
          text: "O valor a depositar não pode ser menor do que 5.000,00 Kz",
          icon: "warning",
          confirmButtonColor: "#3d5476",
          confirmButtonText: "Ok",
          onClose: () => {},
        });
        this.$Progress.fail();
        return;
      }

      try {
        this.form.valor_a_depositar = this.removerFormatacaoAOA(this.form.valor_a_depositar);
        // Faça uma requisição POST para o backend Laravel
        const response = await axios.post("/depositos/update", this.form);

        this.form.reset();
        this.$Progress.finish();
        sweetSuccess(response.data.message);
        
        this.form.valor_a_depositar = this.formatValor(this.form.valor_a_depositar);

        if (response.data.data.tipo_folha == "Ticket") {
          this.imprimirComprovativoTicket(response.data.data);
        }

        if (response.data.data.tipo_folha == "A4") {
          this.imprimirComprovativo(response.data.data);
        }

        // Faça algo com a resposta, se necessário
      } catch (error) {
        
        // Lide com erros, se houver
        sweetError("Primeiro deves fazer abertura do caixa");
        this.form.valor_a_depositar = this.formatValor(this.form.valor_a_depositar);
        this.$Progress.fail();
      }
    },
    
    
    imprimirComprovativo(item) {
      window.open(
        `/depositos/imprimir-comprovativo?codigo=${item.codigo}`,
        "_blank"
      );
    },

    imprimirComprovativoTicket(item) {
      window.open(
        `/imprimir-comprovativo-ticket?codigo=${item.codigo}`,
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
    
    
    