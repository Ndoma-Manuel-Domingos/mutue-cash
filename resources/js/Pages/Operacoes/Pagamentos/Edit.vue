<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-8">
            <h1 class="m-0">Editar Pagamento Nº: {{ pagamento.Codigo }}</h1>
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
          <div class="col-sm-4"></div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12 col-md-3">
            <div class="card">
              <div class="card-header text-center py-4">
                <h1><i class="fa fa-check-circle text-success"></i></h1>
                <h3>Alterar o serviço</h3>
                <p>
                  A alteração de um serviço em um pagamento realizado incorretamente.
                </p>
              </div>

              <div class="card-body text-center">
                <a
                  href=""
                  class="btn-lg btn-primary d-block my-4"
                  data-toggle="modal"
                  data-target="#ModalUpdateServico"
                  >Alterar o serviço</a
                >

                <p>
                  Essa modificação busca corrigir qualquer equívoco relacionado ao serviço inicialmente selecionado, assegurando que o pagamento reflita precisamente a natureza do serviço desejado.
                </p>
              </div>
            </div>
          </div>

          <div class="col-12 col-md-3">
            <div class="card">
              <div class="card-header text-center py-4">
                <h1><i class="fa fa-check-circle text-success"></i></h1>
                <h3>Alterar Nº do estudante</h3>
                <p>
                  A solicitação de alteração do número de estudante em um pagamento feito erroneamente.
                </p>
              </div>

              <div class="card-body text-center">
                <a href="" class="btn-lg btn-primary d-block my-4"
                  data-toggle="modal"
                  data-target="#ModalUpdateNumeroEstudante"
                  >Alterar número do estudante(Matricula)</a
                >

                <p>
                  Ao realizar essa alteração, é essencial fornecer o número correcto do estudante garantindo a integridade e a transparência nos registros financeiros e acadêmicos da instituição.
                </p>
              </div>
            </div>
          </div>

          <div class="col-12 col-md-3">
            <div class="card">
              <div class="card-header text-center py-4">
                <h1><i class="fa fa-check-circle text-success"></i></h1>
                <h3>Editar Factura Completa</h3>
                <p>
                  ..........................................
                </p>
              </div>

              <div class="card-body text-center">
                <!-- <a href="" class="btn-lg btn-primary d-block my-4"
                  >Editar Factura Completa</a
                > -->

                <p>
                  ..........................................
                </p>
              </div>
            </div>
          </div>

          <div class="col-12 col-md-3">
            <div class="card">
              <div class="card-header text-center py-4">
                <h1><i class="fa fa-check-circle text-success"></i></h1>
                <h3>Anular o Pagamento</h3>
                <p>
                  A anulação de um pagamento efetuado erroneamente é um procedimento essencial para corrigir equívocos financeiros.
                </p>
              </div>

              <div class="card-body text-center">
                <a
                  href=""
                  class="btn-lg btn-primary d-block my-4"
                  aria-disabled=""
                  >Anular o Pagamento</a
                >

                <p>
                  Esse processo, geralmente facilitado por mecanismos seguros de gestão de pagamentos, assegura a precisão e a transparência nas operações financeiras, garantindo uma experiência confiável para todas as partes envolvidas.
                </p>
              </div>
            </div>
          </div>
        </div>

        <div class="modal fade" id="ModalUpdateServico">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h4 class="modal-title">Alterar o serviço</h4>
                <button
                  type="button"
                  class="close"
                  data-dismiss="modal"
                  aria-label="Close"
                >
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <form @submit.prevent="submit_updated_servico">
                <div class="modal-body">
                  <div class="row">
                    <div class="col-sm-12 col-12 col-md-12">
                      <div class="form-group">
                        <label>Serviço Antigo</label>
                        <select
                          class="form-control"
                          v-model="form.servico_antigo"
                        >
                          <option value="">Selecionar</option>
                          <option
                            v-for="item in servicos"
                            :key="item"
                            :value="item.Codigo"
                          >
                            {{ item.Descricao }}
                          </option>
                        </select>
                      </div>
                    </div>

                    <div class="col-sm-12 col-12 col-md-12">
                      <div class="form-group">
                        <label>Novo Serviço</label>

                        <select
                          class="form-control"
                          v-model="form.novo_servico"
                        >
                          <option value="">Selecionar</option>
                          <option
                            v-for="item in servicos"
                            :key="item"
                            :value="item.Codigo"
                          >
                            {{ item.Descricao }}
                          </option>
                        </select>
                      </div>
                    </div>
                  </div>
                  
                  <div class="row mt-4">
                    <div class="table-responsive">
                      <table class="table table-hover text-nowrap">
                      <thead>
                        <tr>
                          <th>#</th>
                          <th>Codigo</th>
                          <th>Serviço</th>
                          <th>Ano</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr :key="item.codigo" v-for="item in gets_pagamento_items">
                          <td>#</td>
                          <td>{{ item ? item.codigo : '' }}</td>
                          <td>{{ item.servico ? item.servico.Descricao : '' }}</td>
                          <td>{{ item ? item.Ano : '' }}</td>
                        </tr>
                      </tbody>
                      </table>
                    </div>
                  </div>
                </div>
                <div class="modal-footer justify-content-between">
                  <button
                    type="button"
                    class="btn btn-default"
                    data-dismiss="modal"
                  >
                    Fechar
                  </button>
                  <button type="submit" class="btn btn-primary">
                    Actualizar
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="modal fade" id="ModalUpdateNumeroEstudante">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h4 class="modal-title">Alterar o número do estudante</h4>
                <button
                  type="button"
                  class="close"
                  data-dismiss="modal"
                  aria-label="Close"
                >
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <form @submit.prevent="submit_updated_numero_matricula">
                <div class="modal-body">
                  <div class="row">
                    <div class="col-sm-12 col-12 col-md-6">
                      <div class="form-group">
                        <label>Matricula Actual</label>
                        <input
                          class="form-control"
                          v-model="form_estudante.matricula"
                        >
                      </div>
                    </div>
                    
                    <div class="col-sm-12 col-12 col-md-6">
                      <div class="form-group">
                        <label>Estudante Actual</label>
                        <input
                          class="form-control"
                          v-model="form_estudante.nome"
                        >
                      </div>
                    </div>

                    <div class="col-sm-12 col-12 col-md-12">
                      <div class="form-group">
                        <label>Informa o novo Estudante (Nº Matrícula)</label>
                        <input
                          class="form-control"
                          v-model="form_estudante.nova_matricula"
                          @input="pesqisar_estudante"
                          placeholder="Por favor informe o número de matrícula do estudante!"
                        >
                      </div>
                    </div>
                  </div>
                  
                  <div class="row mt-4">
                    <div class="table-responsive">
                      <table class="table table-hover text-nowrap">
                      <thead>
                        <tr>
                          <th>Matrícula</th>
                          <th>Nome</th>
                          <th>Bilheite</th>
                          <th>Curso</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td>{{ form_estudante ? form_estudante.nova_matricula : '' }}</td>
                          <td>{{ form_estudante ? form_estudante.nome_estudante : '' }}</td>
                          <td>{{ form_estudante ? form_estudante.bilheite_estudante : '' }}</td>
                          <td>{{ form_estudante ? form_estudante.curso_estudante : '' }}</td>
                        </tr>
                      </tbody>
                      </table>
                    </div>
                  </div>
                </div>
                <div class="modal-footer justify-content-between">
                  <button
                    type="button"
                    class="btn btn-default"
                    data-dismiss="modal"
                  >
                    Fechar
                  </button>
                  <button type="submit" class="btn btn-primary">
                    Actualizar
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- /.row -->
      </div>
    </div>
  </MainLayouts>
</template>

<script>
import { sweetSuccess, sweetError } from "../../../components/Alert";
import Paginacao from "../../../Shared/Paginacao";
import { Link } from "@inertiajs/inertia-vue3";

export default {
  props: ["pagamento", "factura", "servicos", "pagamento_items", "gets_pagamento_items", "estadante"],
  components: { Link, Paginacao },
  data() {
    return {
      isUpdate: false,
      form: {
        servico_antigo: this.pagamento_items.Codigo_Servico,
        novo_servico: "",
        codigo: this.pagamento.Codigo,
      },
      form_estudante: {
        nome: this.estadante ? this.estadante.Nome_Completo  : '',
        matricula: this.estadante ? (this.estadante.admissao ? (this.estadante.admissao.matricula ? this.estadante.admissao.matricula.Codigo : '')  : ' ')   : '',
        nova_matricula: '',
        nome_estudante: '',
        bilheite_estudante: '',
        curso_estudante: '',
        codigo_pagamento: this.pagamento.Codigo,
      }
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
  },

  methods: {
    updateData() {
      this.$Progress.start();
      this.$inertia.get("/pagamentos", this.params, {
        preserveState: true,
        preverseScroll: true,
        onSuccess: () => {
          this.$Progress.finish();
        },
      });
    },
    
    pesqisar_estudante(e) {
      e.preventDefault();
      this.$Progress.start();
      $(".table_estudantes").html("");
      axios
        .get(`/pesquisar-estudante?search=${this.form_estudante.nova_matricula}`)
        .then((response) => {
          if (response.data.dados === null) {
            sweetError("Ocorreu um errro");
          } else {
            this.form_estudante.nova_matricula = response.data.dados.Codigo;
            this.form_estudante.nome_estudante = response.data.dados.Nome_Completo;
            this.form_estudante.bilheite_estudante = response.data.dados.Bilhete_Identidade;
            this.form_estudante.curso_estudante = response.data.dados.Designacao;
            sweetSuccess("Estudante Encontrado com sucesso!");
          }
          this.$Progress.finish();
        })
        .catch((errors) => {
          this.$Progress.fail();
          sweetError("Estudante Não Encontrado!");
        });
    },

    async submit_updated_servico() {
      this.$Progress.start();

      if (this.form.novo_servico == "") {
        Swal.fire({
          title: "Atenção",
          text: "O novo serviço deve estar preencher",
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
          const response = await axios.post("../update-servico", this.form);

          this.form.reset();
          this.$Progress.finish();
          sweetSuccess(response.data.message);
          $("#ModalUpdateServico").modal("hide");

          // Faça algo com a resposta, se necessário
        } catch (error) {
          // Lide com erros, se houver
          sweetError("Aconteceu um erro ao actualizar o pagamento!");
          this.$Progress.fail();
        }
      }
    },

    async submit_updated_numero_matricula() {
      this.$Progress.start();

      if (this.form_estudante.nova_matricula == "") {
        Swal.fire({
          title: "Atenção",
          text: "O novo serviço deve estar preencher",
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
          const response = await axios.post("../update-numero-matricula-estudante", this.form_estudante);

          this.form_estudante.reset();
          this.$Progress.finish();
          sweetSuccess(response.data.message);
          $("#ModalUpdateServico").modal("hide");

          // Faça algo com a resposta, se necessário
        } catch (error) {
          // Lide com erros, se houver
          sweetError("Aconteceu um erro ao actualizar o pagamento!");
          this.$Progress.fail();
        }
      }
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


