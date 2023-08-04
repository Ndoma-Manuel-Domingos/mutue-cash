<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Pagamentos</h1>
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
                          <option
                            v-for="item in utilizadores"
                            :key="item"
                            :value="item.utilizadores.codigo_importado"
                          >
                            {{ item.utilizadores.nome }}
                          </option>
                        </select>
                      </div>
                    </div>

                    <div class="col-12 col-md-3">
                      <div class="form-group">
                        <label for="">Anos Lectivos</label>
                        <select v-model="ano_lectivo" class="form-control">
                          <option value="">TODOS</option>
                          <option
                            v-for="item in ano_lectivos"
                            :key="item"
                            :value="item.Codigo"
                          >
                            {{ item.Designacao }}
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
                <Link
                  :href="route('mc.pagamentos.create')"
                  class="btn btn-info float-right"
                  type="button"
                >
                  Novos Pagamentos
                </Link>

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
                      <th>Nº Factura</th>
                      <th>Valor a pagar</th>
                      <th>Valor pago</th>
                      <th>Data da factura</th>
                      <th>Saldo Restante</th>
                      <th class="text-center">Ver detalhes</th>
                      <th class="text-center">Ver Factura</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, index) in items.data" :key="item.Codigo">
                      <td>{{ index + 1 }}</td>
                      <td>{{ item.codigo_factura }}</td>
                      <td>{{ formatValor(item.factura.ValorAPagar) }}</td>
                      <td>{{ formatValor(item.valor_depositado)  }}</td>
                      <td>{{ item.factura ? item.factura.DataFactura : '' }}</td>
                      <td> {{ formatValor(0) }}</td>
                      <td class="text-center">
                        <a @click="detalhes(item.Codigo)" class="text-primary"><i class="fas fa-eye"></i></a>
                      </td>
                      <td class="text-center" v-if="item.factura">
                        <a class="text-danger" href="" @click.prevent="imprimirFatura(item.codigo_factura)"><i class="fas fa-print"></i></a>
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

        <div class="modal fade" id="modal_pagamento">
          <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">

              <div class="modal-header">
                <h5 class="modal-title">Detalhes de pagamento</h5>
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
                        <th>Anexo</th>
                        <!-- <th>Ver recibo</th> -->
                        <th>Feito com saldo</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>{{ items_pagamento.length }}</td>
                        <td>{{ pagamento.Codigo }}</td>
                        <td>{{ pagamento.DataRegisto }}</td>
                        <td class="text-center">{{ formatValor(pagamento.valor_depositado) }}</td>
                        <!-- <td class="text-center" v-if="pagamento.estado == 1"><span class="text-success">Validado</span></td>
                        <td class="text-center" v-if="pagamento.estado == 2"><span class="text-warning">Pendente</span></td>
                        <td class="text-center" v-if="pagamento.estado == 3"><span class="text-danger">Rejeitado</span></td>
                        <td class="text-center" v-else><span class="text-success">Validado</span></td> -->
                        <td class="text-center" ><span class="text-success">Validado</span></td>
                        <td class="text-center">{{ pagamento.updated_at }}</td>
                        <td class="text-center"><a :href="'https://mutue.ao/storage/documentos/'+pagamento.nome_documento" target="_blink"><i class="fas fa-paperclip"></i></a></td>
                        <!-- <td class="text-center"><a href="" @click.prevent="imprimirFatura(pagamento.codigo_factura)"><i class="fas fa-print"></i></a></td> -->
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
                        <th class="text-right">Total</th>
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
                        <td class="text-right">{{ formatValor(item.Valor_Total) }}</td>
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
      data_inicio: "",
      data_final: "",
      operador: "",
      ano_lectivo: "",

      params: {},

      pagamento: [],
      items_pagamento: [],

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

    detalhes(Codigo){
      this.loading = true;
      axios
        .get(`/pagamentos/${Codigo}/detalhes`)
        .then((response) => {
        console.log(response);
          this.pagamento = response.data.data;
          this.items_pagamento = response.data.items;

          $("#modal_pagamento").modal("show");

        })
        .catch((error) => {});
    },

    formatValor(atual) {
      const valorFormatado = Intl.NumberFormat("pt-br", {
        style: "currency",
        currency: "AOA",
      }).format(atual);
      return valorFormatado;
    },


    imprimirFatura(codigo_fatura) {
      window.open("/fatura/diversos/" + btoa(btoa(btoa(codigo_fatura))));
    },


    imprimirPDF() {
      window.open(
        `/pagamentos/pdf?operador=${this.operador}&ano_lectivo=${this.ano_lectivo}&data_inicio=${this.data_inicio}&data_final=${this.data_final}`,
        "_blank"
      );
    },

    imprimirEXCEL() {
      window.open(
        `/pagamentos/excel?operador=${this.operador}&ano_lectivo=${this.ano_lectivo}&data_inicio=${this.data_inicio}&data_final=${this.data_final}`,
        "_blank"
      );
    },
  },
};
</script>


