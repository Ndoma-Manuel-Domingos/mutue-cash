<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-8">
            <h1 class="m-0">Depósito de valores efetuados no período de {{ formatarData(data_inicio) }} a {{ formatarData(data_final) }}</h1>
          </div>
          <div class="col-sm-4">
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
            <div class="card">
              <form action="">
                <div class="card-body">
                  <div class="row">
                  
                    <div class="col-12 col-md-3">
                      <div class="form-group">
                        <label for="">Operadores</label>
                          <select v-model="operador" class="form-control">
                            <option value="">TODOS</option>
                            <option v-for="item in utilizadores" :key="item" :value="item.utilizadores.codigo_importado">
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
                      <th>Nº Matricula</th>
                      <th>Nº Candidatura</th>
                      <th>Estudante</th>
                      <th>Valor depositado</th>
                      <th>Reserva após Depósito</th>
                      <!-- <th>Forma Pagamento</th> -->
                      <th>Operador</th>
                      <th>Ano Lectivo</th>
                      <th>Data</th>
                      <th>Acções</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in items.data" :key="item.codigo">
                      <td>{{ item.codigo }}</td>
                      <td>{{ item.codigo_matricula_id?? 'Candidato'}}</td>
                      <td>{{item.Codigo_PreInscricao?? 'Estudante Regular'}}</td>
                      <td>
                        {{ item.matricula ? item.matricula.admissao.preinscricao.Nome_Completo : item.candidato ? item.candidato.Nome_Completo : '' }}
                      </td>
                      <td>{{ formatValor(item.valor_depositar) }}</td>
                      <td>{{ formatValor(item.saldo_apos_movimento) }}</td>
                      <!-- <td>{{ item.forma_pagamento.descricao }}</td> -->
                      <td>{{ item.user ? item.user.nome : '' }}</td>
                      <td>{{ item.ano_lectivo ? item.ano_lectivo.Designacao: '' }}</td>
                      <td>{{ item.created_at }}</td>
                      <td class="text-center">
                        <Link @click="imprimirComprovativo(item)">
                          <i class="fas fa-print text-danger"></i>
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
            <h4 class="modal-title">Novo Depósito</h4>
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
                        @keyup="formatarMoeda()"
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
    props: ["items", "utilizadores", "total_depositado", "valor_a_depositar_padrao"],
    components: { Link, Paginacao },
    data() {
      return { 
    
        isUpdate: false,
        itemId: null,
        
        data_inicio: new Date().toISOString().substr(0, 10),
        data_final: new Date().toISOString().substr(0, 10),
        operador: "",
        
        depositos: [],
        params: {},
        
        contemDeposito: false,
        
        form: this.$inertia.form({
          codigo_matricula: null,
          candidato_id: "",
          disabled: false,
          disabled2: false,
          // falta ser paramentrizado 5000
          valor_a_depositar: this.valor_a_depositar_padrao.Valor ?? 0,
          nome_estudante: null,
          bilheite_estudante: null,
          curso_estudante: null,
        }),
    };
  },
  
  mounted() {
    this.params.data_inicio = this.data_inicio;
    this.form.valor_a_depositar = this.formatValor(this.form.valor_a_depositar)
    // this.params.data_final = this.data_final;
    this.updateData();
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

    candidato_id: function (val) {
      this.params.candidato_id = val;
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
    
    formatarMoeda() {
      // Remover caracteres que não são números
      let valor = this.form.valor_a_depositar.replace(/\D/g, '');

      // Converter o valor para número
      valor = Number(valor) / 100; // Dividir por 100 para ter o valor em reais

      // Formatar o número para moeda
      this.form.valor_a_depositar = valor.toLocaleString('pt-BR', {
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

    async submit() {
    
      this.$Progress.start();
              
      if (this.removerFormatacaoAOA(this.form.valor_a_depositar) < 5000) {
        Swal.fire({
          title: "Atenção",
          text: "O valor a depositar não pode ser menor do que 5.000,00 Kz",
          icon: "warning",
          confirmButtonColor: "#3d5476",
          confirmButtonText: "Ok",
          onClose: () => { },
        });
        this.$Progress.fail();
        return;
      }
    
      if (this.isUpdate) {
        
      } else {
        
        try {
        
          this.form.valor_a_depositar = this.removerFormatacaoAOA(this.form.valor_a_depositar);
          // Faça uma requisição POST para o backend Laravel
          const response = await axios.post('/depositos/store', this.form);
          
          this.form.reset();
          this.$Progress.finish();
          sweetSuccess(response.data.message);
          $("#modalDeposito").modal("hide");
          this.form.valor_a_depositar = this.formatValor(this.form.valor_a_depositar)
          
          this.imprimirComprovativo(response.data.data);
          
            // Faça algo com a resposta, se necessário
        } catch (error) {
          // Lide com erros, se houver
          sweetError("Primeiro deves fazer abertura do caixa");
          this.form.valor_a_depositar = this.formatValor(this.form.valor_a_depositar)
          this.$Progress.fail();
        }
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
    
    editarItem(item) {   
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

    disableTo(){
      if(this.codigo_matricula){
        this.disabled2=false;
        this.disabled=true;
      }else if(this.candidato_id){
        this.disabled2=true;
        this.disabled=false;
      }else{
        this.disabled2=false;
        this.disabled=false;
      }
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
    },

    voltarPaginaAnterior() {
      window.history.back();
    },
    
  },
};
</script>
  
  
  