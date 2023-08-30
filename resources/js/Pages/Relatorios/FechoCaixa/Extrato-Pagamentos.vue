<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Extratos de Pagamentos de {{ formatarData(data_inicio) }} a {{ formatarData(data_final) }}</h1>
          </div>
          <div class="col-sm-6">
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
                    <div class="col-12 col-md-2" >
                      <div class="form-group">
                        <label for="">Nº do Estudante</label>
                        <input
                          type="text"
                          placeholder="informe o número da matricula do estudante!"
                          class="form-control"
                          :disabled="disabled2"
                          @keyup="disableTo"
                          v-model="codigo_matricula"
                        />
                      </div>
                    </div>
                    <div class="col-12 col-md-2">
                      <div class="form-group">
                        <label for="">Nº do Candidato</label>
                        <input
                          type="text"
                          placeholder="informe o número do candidato!"
                          class="form-control"
                          :disabled="disabled"
                          @keyup="disableTo"
                          v-model="candidato_id"
                        />
                      </div>
                    </div>

                    <div class="col-12 col-md-2">
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

                    <div class="col-12 col-md-2">
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
                      <th>Item</th>
                      <th>Estudante</th>
                      <th>Nº Factura</th>
                      <th>Valor a pagar</th>
                      <th>Valor pago</th>
                      <th>Data da factura</th>
                      <th>Reserva Actual</th>
                      <th>Operador</th>
                      <th class="text-center">Ver detalhes</th>
                      <th class="text-center">Impressões</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, index) in items.data" :key="item.Codigo">
                      <td>{{ index + 1 }}</td>
                      <td>{{ item.factura.matriculas ? item.factura.matriculas.admissao.preinscricao.Nome_Completo : item.preinscricao ? item.preinscricao.Nome_Completo:NULL }}</td>
                      <td>{{ item.codigo_factura }}</td>
                      <td>{{ formatValor(item.factura.ValorAPagar) }}</td>
                      <td>{{ formatValor(item.valor_depositado)  }}</td>
                      <td>{{ item.factura ? item.factura.DataFactura : '' }}</td>
                      <td>{{ item.factura ? formatValor(item.factura.Troco): formatValor(0) }}</td>
                      <td>{{ item.operador_novos ? item.operador_novos.nome : item.operador_antigo ? item.operador_antigo.nome : NULL }}</td>
                      <td class="text-center">
                        <a href="#" @click="detalhes(item.Codigo)" class="text-primary"><i class="fas fa-eye"></i></a>
                      </td>
                      <td class="text-center" v-if="item.factura">
                        <a class="text-danger" title="REIMPRIMIR A FACTURA" href="#" @click.prevent="imprimirFatura(item.codigo_factura)"><i class="fas fa-print"></i></a>
                        &nbsp;&nbsp;
                        <a class="text-primary" href="#" title="IMPRIMIR O EXTRACTO" @click.prevent="imprimirDetalhesPagamento(item.Codigo)"><i class="fas fa-print"></i></a>
                      </td>
                      <td class="text-center" v-else>
                        <a class="text-secondary" href=""><i class="fas fa-print"></i></a>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="card-footer">
                <Link href="" class="text-secondary">
                  TOTAL REGISTROS: {{ items.total }}
                </Link>
                <Paginacao
                  :links="items.links"
                  :prev="items.prev_page_url"
                  :next="items.next_page_url"
                />
              </div>
            </div>
          </div>
        </div>


        <!-- DETALHES DO PAGAMENTO -->
        <div class="modal fade" id="modal_pagamento">
          <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Detalhes do extrato de pagamentos</h5>
                <div class="row">
                  <div class="col-sm-6">
                    <button class="btn btn-primary float-right mr-1" type="button" @click="imprimirDetalhesPagamento(pagamento.Codigo)">
                      <i class="fas fa-print"></i>
                    </button>
                  </div>
                  <div class="col-sm-6">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                    </button>
                  </div>
                </div>
              </div>

              <div class="modal-body">
                <div class="table-responsive">
                  <table class="table-sm text-nowrap">
                    <tbody>
                      <tr> <th>Nº da factura: {{ pagamento.codigo_factura }}</th> </tr>
                      <tr> <th>1 pagamento(s) efectuado(s)</th> </tr>
                      <tr> <th>Data da factura: {{ DataFactura }}</th> </tr>
                      <tr> <th>Valor total a pagar: {{ formatValor(pagamento.Totalgeral) }} </th> </tr>
                      <tr> <th>Valor pago pelo serviço: {{ formatValor(pagamento.Totalgeral) }} </th> </tr>
                      <tr> <th>Valor em dívida: {{ formatValor(0) }} </th> </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="modal-header bg-info py-1">
                <h6 class="modal-title">Pagamento</h6>
              </div>

              <div class="modal-body">
                <div class="table-responsive">
                  <table class="table-sm table-bordered table-hover text-nowrap" style="width: 100%;">
                    <thead>
                      <tr>
                        <th>Items</th>
                        <th>Nº Pagamento</th>
                        <th>Data de envio do pag.</th>
                        <th>Valor depositado</th>
                        <th>Estado</th>
                        <th>Data da validação</th>
                        <!-- <th>Anexo</th> -->
                        <!-- <th>Ver recibo</th> -->
                        <th>Feito com a Reserva?</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>{{ items_pagamento.length }}</td>
                        <td>{{ pagamento.Codigo }}</td>
                        <td>{{ pagamento.DataRegisto }}</td>
                        <td class="text-center">{{ formatValor(pagamento.valor_depositado) }}</td>
                        <td class="text-center" ><span class="text-success">Validado</span></td>
                        <td class="text-center">{{ pagamento.updated_at }}</td>
                        <!-- <td class="text-center"><a :href="'https://mutue.ao/storage/documentos/'+pagamento.nome_documento" target="_blink"><i class="fas fa-paperclip"></i></a></td> -->
                        <td class="text-center"> Não</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="modal-header bg-info py-1">
                <h6 class="modal-title">Items do Pagamento</h6>
              </div>

              <div class="modal-body">
                <div class="table-responsive">
                  <table class="table-sm table-bordered table-hover text-nowrap" style="width: 100%;">
                    <thead>
                      <tr>
                        <th>Item</th>
                        <th>Serviço/UC</th>
                        <th>Prestação</th>
                        <th>Valor</th>
                        <th>Multa</th>
                        <th>Desconto</th>
                        <th class="text-center">Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(item, index) in items_pagamento" :key="item">
                        <td>{{ index + 1 }}</td>
                        <td>{{ item.servico.Descricao }}</td>
                        <td>{{ item.mes_temps ? item.mes_temps.designacao : ( item.mes ? item.mes.mes : '#') }}</td>
                        <td>{{ formatValor(item.Valor_Pago) }}</td>
                        <td>{{ formatValor(item.Multa) }}</td>
                        <td>{{ formatValor(item.Deconnto) }}</td>
                        <td class="text-center">{{ formatValor(item.Valor_Total) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="modal-footer justify-content-between">
              </div>
            </div>
          </div>
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
  props: ["items", "ano_lectivos", "utilizadores"],
  components: { Link, Paginacao },
  data() {
    return {
      data_inicio: new Date().toISOString().substr(0, 10),
      data_final: new Date().toISOString().substr(0, 10),
      operador: "",
      ano_lectivo: "",
      codigo_matricula: "",
      candidato_id: "",

      params: {},

      pagamento: [],
      items_pagamento: [],
      disabled: false,
      disabled2: false,

    };
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
    operador: function (val) {
      this.params.operador = val;
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
    codigo_matricula: function (val) {
      this.params.codigo_matricula = val;
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
      this.$inertia.get("/relatorios/extrato-pagamentos", this.params, {
        preserveState: true,
        preverseScroll: true,
        onSuccess: () => {
          this.$Progress.finish();
        },
        onError: () => {
          Swal.fire({
            title: "Alerta",
            text: "O número de estudante/candidatura informado não existe!",
            icon: "error",
            confirmButtonColor: "#3d5476",
            confirmButtonText: "Ok",
            onClose: () => {},
          });
        },
      });
    },

    detalhes(Codigo){
      this.loading = true; 
      axios
        .get(`/pagamentos/${Codigo}/detalhes`)
        .then((response) => {
        console.log(response);
          this.pagamento = response.data.data;
          this.items_pagamento = response.data.items;

          $("#modal_pagamento").modal("show");

        }).catch((error) => {});
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

    imprimirFatura(codigo_fatura) {
      window.open("/fatura/diversos/" + btoa(btoa(btoa(codigo_fatura))));
    },
    
    // ${!this.codigo_matricula?this.candidato_id:this.codigo_matricula}


    imprimirPDF() {
      window.open(`/relatorios/fecho-caixa/operador/pdf?${this.codigo_matricula ? 'codigo_matricula='+this.codigo_matricula:(this.candidato_id ? 'candidato_id='+this.candidato_id:'')}&data_inicio=${this.data_inicio}&data_final=${this.data_final}`, "_blank");
    },

    imprimirEXCEL() {
      window.open(`/relatorios/fecho-caixa/operador/excel`, "_blank");
    },

    imprimirDetalhesPagamento(Codigo) {
      this.loading = true; 
      window.open(`/pagamentos/imprmir/${Codigo}/detalhes`, "_blank");
    },

    // imprimirEXCEL() {
    //   window.open(
    //     `/pagamentos/excel?operador=${this.operador}&ano_lectivo=${this.ano_lectivo}&data_inicio=${this.data_inicio}&data_final=${this.data_final}`,
    //     "_blank"
    //   );
    // },

    voltarPaginaAnterior() {
      window.history.back();
    },
  },
};
</script>


