<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-8">
            <h1 class="m-0">Listas de Caixas </h1>
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
                  
                    <!-- <div class="col-12 col-md-3">
                      <div class="form-group">
                        <label for="">Operadores</label>
                          <select v-model="operador" class="form-control">
                            <option value="">TODOS</option>
                            <option v-for="item in utilizadores" :key="item" :value="item.utilizadores.codigo_importado">
                              {{ item.utilizadores.nome ?? '' }}
                            </option>
                          </select>
                        </div>
                    </div> -->
                  
                    <!-- <div class="col-12 col-md-3">
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
                    </div> -->
                    
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
                  <i class="fas fa-list"></i>
                  {{ (total_geral) }} Registos
                </button>
              
                <button
                  class="btn btn-info float-right"
                  type="button"
                  data-toggle="modal"
                  data-target="#modalCaixas"
                >
                  <i class="fas fa-plus"></i>
                  Novos Caixas
                </button>
                
                <button
                  class="btn btn-success float-right mr-1"
                  type="button">
                  <i class="fas fa-file-excel"></i>
                  EXCEL
                </button>
                
                <button
                  class="btn btn-danger float-right mr-1"
                  type="button">
                  <i class="fas fa-file-pdf"></i>
                  PDF
                </button>
              </div>

              <div class="table-responsive">
                <table class="table table-hover text-nowrap">
                  <thead>
                    <tr>
                      <th>Nº Ordem</th>
                      <th>ID</th>
                      <th>Designação</th>
                      <th>Código de Bloqueio</th>
                      <th>Status</th>
                      <th>Estado de Bloqueio</th>
                      <th>Criado Por</th>
                      <th>Data de Criação</th>
                      <th>Data de Actualização</th>
                      <th>Acções</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, index) in items.data" :key="item.codigo">
                      <td>{{ ++index }}</td>
                      <td>{{ item.codigo ?? ''}}</td>
                      <td class="text-bold text-info">{{ item.nome ?? ''}}</td>
                      <td :class="item.code ? (item.code!=='null' ? 'text-bold text-center':'text-red text-bold text-center'): 'text-black text-center'">{{ item.code ?? 'NULL'}}</td>
                      <td :class="item.status ? (item.status=='aberto' ? 'text-success text-bold':'text-red text-bold'): 'text-black'" style="text-transform: capitalize;">{{ item.status ?? 'Indefinido' }}</td>
                      <td :class="item.bloqueio ? (item.bloqueio=='Y' ? 'text-success text-bold':'text-red text-bold'): 'text-black'">{{ item.bloqueio ? (item.bloqueio=='Y' ? 'Bloqueado':'Desbloqueado'): 'Não definido' }}</td>
                      <td>{{ item.operador_que_abriu ? item.operador_que_abriu.nome: 'Sem operador' }}</td>
                      <td>{{ item.created_at ? item.created_at: 'Sem data' }}</td>
                      <td>{{ item.updated_at ? item.updated_at: 'Sem actualização'}}</td>
                      <td class="text-center">
                        <bottom href="#" class="btn-sm btn-success mx-1" @click="editarItem(item)">
                          <i class="fas fa-edit "></i>
                          Editar
                        </bottom>
                      </td>
                      <td class="text-center">
                        <bottom type="button" @click="deleteItem(item)" class="btn-sm btn-danger mx-1">
                          <i class="fas fa-edit "></i>
                          Excluir
                        </bottom>
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

    <!-- MODAL REGISTAR NOVO CAIXA  -->
    <div class="modal fade" id="modalCaixas">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Novo Caixa</h4>
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
                  <div class="form-group">
                    <label for="" class="form-label">Designação do Caixa</label>
                    <input
                      type="text"
                      v-model="form.nome"
                      class="form-control"
                      placeholder="CAIXA N º1"
                    />
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer justify-content-between">
              <button type="button" class="btn btn-black" data-dismiss="modal">
                Fechar
              </button>
              <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- MODAL EDITAR CAIXA  -->
    <div class="modal fade" id="modalEditCaixa">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">EDITAR CAIXA</h4>
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
                <div class="col-12 col-md-6 mb-3">
                  <div class="form-group">
                    <label for="" class="form-label">Designação do Caixa</label>
                    <input
                      type="text"
                      v-model="form.nome"
                      class="form-control"
                      placeholder="Designação do Caixa"
                    />
                  </div>
                </div>

                <div class="col-12 col-md-6 mb-3">
                  <div class="form-group">
                    <label for="" class="form-label">Estado do Caixa</label>
                    <input
                      type="text"
                      v-model="form.status"
                      class="form-control"
                      placeholder="Estado do Caixa"
                    />
                  </div>
                </div>
              </div>
                
              <div class="row">
                <div class="col-12 col-md-6 mb-3">
                  <div class="form-group">
                    <label for="" class="form-label">Estado de Bloqueio</label>
                    <input
                      type="text"
                      v-model="form.bloqueio"
                      class="form-control"
                      placeholder="Estado de Bloqueio"
                    />
                  </div>
                </div>

                <div class="col-12 col-md-6 mb-3">
                  <div class="form-group">
                    <label for="" class="form-label">Código de Bloqueio</label>
                    <input
                      type="text"
                      v-model="form.code"
                      class="form-control"
                      placeholder="Código de Bloqueio"
                    />
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer justify-content-between">
              <button type="button" class="btn btn-black" data-dismiss="modal">
                Fechar
              </button>
              <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!--Modal Eliminar Caixa -->
    <div class="modal fade" id="modalEliminarCaixa">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Eliminar Caixa</h4>
            <button
              type="button"
              class="close"
              data-dismiss="modal"
              aria-label="Close"
            >
              <span aria-hidden="true">&times;</span>
            </button>
          </div>

          <div class="modal-body">
            <h5 class="text-bold text-danger">Tem certeza que pretende excluir o {{ caixa.nome }} ?</h5>
          </div>
          <div class="modal-footer justify-content-between">
            <button type="button" class="btn btn-black" data-dismiss="modal">
              NÃO
            </button>
            <button type="submit" @click="confirmDeleteItem(caixa)" class="btn btn-primary">SIM</button>
          </div>
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
    props: ["items", "total_geral"],
    components: { Link, Paginacao },
    data() {
      return { 
    
        isUpdate: false,
        itemId: null,
        
        data_inicio: new Date().toISOString().substr(0, 10),
        data_final: new Date().toISOString().substr(0, 10),
        operador: "",

        params: {},
        caixa: {codigo:0},

        editedIndex: -1,
        
        form: this.$inertia.form({
          disabled: false,
          disabled2: false,
        }),
    };
  },
  
  mounted() {
    this.params.data_inicio = this.data_inicio;

    // this.updateData();
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
    // data_inicio: function (val) {
    //   this.params.data_inicio = val;
    //   this.updateData();
    // },
    // data_final: function (val) {
    //   this.params.data_final = val;
    //   this.updateData();
    // },
  },
  
  methods: {
    // updateData() {
    //   this.$Progress.start();
    //   this.$inertia.get("/depositos/operacoes/caixas", this.params, {
    //     preserveState: true,
    //     preverseScroll: true,
    //     onSuccess: () => {
    //       this.$Progress.finish();
    //     },
    //   });
    // },
    
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
              
      if (!this.form) {
        Swal.fire({
          title: "Atenção",
          text: "O formulário está sem dados, preencha-o por favor",
          icon: "warning",
          confirmButtonColor: "#3d5476",
          confirmButtonText: "Ok",
          onClose: () => { },
        });
        this.$Progress.fail();
        return;
      }
    
      if (this.form.codigo > 1) {

        try {
          // Faça uma requisição POST para o backend Laravel
          
          const response = await axios.post('/operacoes/caixas/update', this.form);
          
          this.form.reset();
          this.$Progress.finish();
          sweetSuccess(response.data.message);
          this.updateData();
          $("#modalEditCaixa").modal("hide");
        } catch (error) {
          // Lide com erros, se houver
          sweetError(error.data.message);
          this.$Progress.fail();
        }
        
      } else {
        
        try {
          // Faça uma requisição POST para o backend Laravel
          const response = await axios.post('/operacoes/caixas/store', this.form);
          
          this.form.reset();
          this.$Progress.finish();
          sweetSuccess(response.data.message);
          this.updateData();
          $("#modalCaixas").modal("hide");
        } catch (error) {
          // Lide com erros, se houver
          sweetError(error.data.message);
          this.$Progress.fail();
        }
      }
    
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
      this.form = item;
      // this.editedIndex = this.items.indexOf(item);
      $("#modalEditCaixa").modal("show");
    },

    deleteItem(item) {  
      this.caixa = item;
      $("#modalEliminarCaixa").modal("show");
    },

    confirmDeleteItem(item) {  
      this.caixa = item;
      axios.get(`/operacoes/caixas/delete/${item.codigo}`).then((response) => {
        this.$Progress.finish();
        sweetSuccess(response.data.message);
        this.updateData();
        $("#modalCaixas").modal("hide");
      })
      .catch((error) => {
        this.$Progress.fail();
        sweetError(error.data.message);
      });
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
  
  
  