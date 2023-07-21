<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Depositos</h1>
          </div>
          <div class="col-sm-6"></div>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12 col-md-12">
            <div class="card">
              <form action="">
                <div class="card-body">
                  <div class="row">
                  
                    <div class="col-12 col-md-3">
                      <div class="form-group">
                        <label for="">Operadores</label>
                          <select v-model="operador" class="form-control">
                            <option value="">TODOS</option>
                            <option v-for="item in utilizadores" :key="item" :value=item.utilizadores.codigo_importado>
                              {{ item.utilizadores.nome ?? '' }}
                            </option>
                          </select>
                        </div>
                    </div>
                  
                    <div class="col-12 col-md-3">
                      <div class="form-group">
                        <label for="">Data Inicio</label>
                        <input
                          type="date"
                          placeholder="informe do Inicio"
                          class="form-control"
                          v-model="data_inicio"
                        />
                      </div>
                    </div>
                    
                    <div class="col-12 col-md-3">
                      <div class="form-group">
                        <label for="">Data Final</label>
                        <input
                          type="date"
                          placeholder="informe do final"
                          class="form-control"
                          v-model="data_final"
                        />
                      </div>
                    </div>
                    
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-12 col-md-12">
            <div class="card">
              <div class="card-header">
                
                <button
                  class="btn btn-info float-left"
                  type="button"
                >
                  <i class="fas fa-money-bill"></i>
                  {{ formatValor(total_depositado) }}
                </button>
              
                <button
                  class="btn btn-info float-right"
                  type="button"
                  data-toggle="modal"
                  data-target="#modalDeposito"
                >
                  <i class="fas fa-plus"></i>
                  Novos Depositos
                </button>
                
                <button
                  class="btn btn-success float-right mr-1"
                  type="button"
                  @click="imprimirEXCEL"
                >
                  <i class="fas fa-file-excel"></i>
                  EXCEL
                </button>
                
                <button
                  class="btn btn-danger float-right mr-1"
                  type="button"
                  @click="imprimirPDF"
                >
                  <i class="fas fa-file-pdf"></i>
                  PDF
                </button>
              </div>

              <div class="table-responsive">
                <table class="table table-hover text-nowrap">
                  <thead>
                    <tr>
                      <th>Nº Deposito</th>
                      <th>Matricula</th>
                      <th>Estudante</th>
                      <th>Saldo depositado</th>
                      <th>Saldo apos Movimento</th>
                      <th>Forma Pagamento</th>
                      <th>Operador</th>
                      <th>Ano Lectivo</th>
                      <th>Data</th>
                      <th>Acções</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in items.data" :key="item.codigo">
                      <td>{{ item.codigo }}</td>
                      <td>{{ item.codigo_matricula_id }}</td>
                      <td>
                        {{ item.matricula.admissao.preinscricao.Nome_Completo }}
                      </td>
                      <td>{{ formatValor(item.valor_depositar) }}</td>
                      <td>{{ formatValor(item.saldo_apos_movimento) }}</td>
                      <td>{{ item.forma_pagamento.descricao }}</td>
                      <td>{{ item.user ? item.user.nome : '' }}</td>
                      <td>{{ item.ano_lectivo.Designacao }}</td>
                      <td>{{ item.created_at }}</td>
                      <td>
                        <Link class="btn-sm btn-primary" @click="imprimirComprovativo(item)">
                          <i class="fas fa-print "></i>
                          Imprimir
                        </Link>
                        <!-- <Link class="btn-sm btn-success mx-1" @click="editarItem(item)">
                          <i class="fas fa-edit "></i>
                          Editar
                        </Link> -->
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="card-footer">
                <Link href="" class="text-secondary">
                TOTAL REGISTROS: {{ items.data.length }}
                </Link>
                <Paginacao :links="items.links"
                    :prev="items.prev_page_url"
                    :next="items.next_page_url" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="modalDeposito">
      <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Novo Deposito</h4>
            <button
              type="button"
              class="close"
              data-dismiss="modal"
              aria-label="Close"
            >
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <form action="" @submit.prevent="submit">
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
                          type="search"
                          placeholder="Introduz o Número da matricula!"
                          aria-label="Search"
                        />
                        <div class="input-group-append">
                          <button
                            class="btn btn-info"
                            @click="pesqisar_estudante"
                          >
                            <i class="fas fa-search fa-fw"></i>
                          </button>
                        </div>
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
                    <label for="" class="form-label">Valor a depositar</label>
                    <div class="input-group">
                      <input
                        type="text"
                        v-model="form.valor_a_depositar"
                        class="form-control"
                        placeholder="informe o valor a depositar"
                        @input="verificarLetras"
                      />
                      <div class="input-group-append">
                        <button type="button" class="btn btn-info">kz</button>
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
              <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </MainLayouts>
</template>
  
<script>
  import { sweetSuccess, sweetError } from "../../../components/Alert";
  import Paginacao from "../../../Shared/Paginacao"
  import { Link } from "@inertiajs/inertia-vue3";

  
  export default {
    props: ["items", "utilizadores", "total_depositado"],
    components: { Link, Paginacao },
    data() {
      return { 
    
        isUpdate: false,
        itemId: null,
        
        data_inicio: "",
        data_final: "",
        operador: "",
        
        depositos: [],
        params: {},
        
        contemLetras: false,
        contemDeposito: false,
        
        form: this.$inertia.form({
          codigo_matricula: null,
          // falta ser paramentrizado 5000
          valor_a_depositar: 5000,
          nome_estudante: null,
          bilheite_estudante: null,
          curso_estudante: null,
        }),
    };
  },

  watch: {
    options: function (val) {
      this.params.page = val.page;
      this.params.page_size = val.itemsPerPage;
      if (val.sortBy.length != 0) {
        this.params.sort_by = val.sortBy[0];
        this.params.order_by = val.sortDesc[0] ? "desc" : "asc";
      }else {
        this.params.sort_by = null;
        this.params.order_by = null;
      }
      this.updateData();
    },
    operador: function (val) {
      this.params.operador = val;
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
      this.$inertia.get("/depositos", this.params, {
        preserveState: true,
        preverseScroll: true,
        onSuccess: () => {
          this.$Progress.finish();
        },
      });
    },
    
    verificarLetras() {
      // Expressão regular que verifica se a string contém letras (a-zA-Z)
      const regexLetras = /[a-zA-Z]/;
      this.contemLetras = regexLetras.test(this.form.valor_a_depositar);
    },

    submit() {
    
      if (this.contemLetras) {
        Swal.fire({
          title: "Atenção",
          text: "O valor a depositar não pode conter letras!",
          icon: "warning",
          confirmButtonColor: "#3d5476",
          confirmButtonText: "Ok",
          onClose: () => { },
        });
        return;
      }
    
      if (this.form.valor_a_depositar < 5000) {
        Swal.fire({
          title: "Atenção",
          text: "O valor a depositar não pode ser menor do que 5.000,00 Kz",
          icon: "warning",
          confirmButtonColor: "#3d5476",
          confirmButtonText: "Ok",
          onClose: () => { },
        });
        return;
      }
      
      // if (this.form.valor_a_depositar > 1000000) {
      //   Swal.fire({
      //     title: 'Atenção?',
      //     text: "O Valor a depositar é superior a 1.000.000,00 Kz, Deseja continuar com este deposito!",
      //     icon: 'warning',
      //     showCancelButton: true,
      //     confirmButtonColor: '#3085d6',
      //     cancelButtonColor: '#d33',
      //     confirmButtonText: 'Sim, desejo continuar!'
      //   }).then((result) => {
      //     if (result.isConfirmed) {
      //       this.contemDeposito = true;
      //     }else {
      //       this.contemDeposito = false;
      //     }
      //   })
        
      //   return this.contemDeposito;
      // }
      
      // if(!this.contemDeposito){
      
      //   alert(this.contemDeposito)
      
      //   return
      // }
    
      this.$Progress.start();
      
      if (this.isUpdate) {
      
      } else {
        this.form.post(route("mc.depositos.store"), {
          preverseScroll: true,
          onSuccess: (data) => {
            // console.log(data)
            this.form.reset();
            this.$Progress.finish();
            sweetSuccess("Deposito realizado com sucesso!");
            $("#modalDeposito").modal("hide");
          },
          onError: (errors) => {
            sweetError("Não foi possível fazer o deposito!");
            this.$Progress.fail();
          },
        });
      }
    },

    pesqisar_estudante(e) {
      e.preventDefault();
      this.$Progress.start();
      $(".table_estudantes").html("");
      axios
        .get(`/pesquisar-estudante?search=${this.form.codigo_matricula}`)
        .then((response) => {
          if (response.data.dados === null) {
            sweetError("Ocorreu um errro");
          } else {
            this.form.codigo_estudante = response.data.dados.Codigo;
            this.form.nome_estudante = response.data.dados.Nome_Completo;
            this.form.bilheite_estudante = response.data.dados.Bilhete_Identidade;
            this.form.curso_estudante = response.data.dados.Designacao;
            sweetSuccess("Estudante Encontrado com sucesso!");
          }
          this.$Progress.finish();
        })
        .catch((errors) => {
          this.$Progress.fail();
          sweetError("Estudante Não Encontrado!");
        });
    },

    applyCurrencyMask() {
      // Remove todos os caracteres não numéricos, exceto o ponto decimal
      let value = this.form.valor_a_depositar.replace(/[^\d.]/g, "");

      // Separa o valor inteiro dos centavos
      let [integerPart, decimalPart] = value.split(".");

      // Formata o valor inteiro adicionando pontos para separar os milhares
      integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ".");

      // Combina novamente o valor inteiro e decimal
      let formattedValue =
        decimalPart !== undefined
          ? `${integerPart}.${decimalPart}`
          : integerPart;

      // Adiciona o símbolo de moeda
      formattedValue = `Kz ${formattedValue}`;

      // Atualiza o valor no objeto de dados
      this.form.valor_a_depositar = formattedValue;
    },

    formatValor(atual) {
      const valorFormatado = Intl.NumberFormat("pt-br", {
        style: "currency",
        currency: "AOA",
      }).format(atual);
      return valorFormatado;
    },
    
    editarItem(item) 
    {   
      this.form.clearErrors();
      
      // this.form.clearErrors();
      // this.form.codigo_matricula = item.codigo_matricula_id,
      // this.form.valor_a_depositar = item.valor_depositar,
      // this.form.nome_estudante = item.matricula.admissao.preinscricao.Nome_Completo,
      // this.form.bilheite_estudante = item.matricula.admissao.preinscricao.Bilhete_Identidade,
      // this.form.curso_estudante = item.matricula.admissao.preinscricao.Bilhete_Identidade,
      
      // this.isUpdate = true;
      // this.itemId = item.codigo;

    },
    
    imprimirPDF() {
      window.open(`/depositos/pdf?data_inicio=${this.data_inicio}&data_final=${this.data_final}`, "_blank");
    },
    
    imprimirEXCEL() {
      window.open(`/depositos/excel?data_inicio=${this.data_inicio}&data_final=${this.data_final}`, "_blank");
    },
    
    imprimirComprovativo(item) 
    {
      window.open(`/depositos/imprimir-comprovativo?codigo=${item.codigo}`, "_blank");
    }
    
  },
};
</script>
  
  
  