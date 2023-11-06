<template>
  <MainLayouts>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h3 class="m-0">Extracto de Caixa por Oparador de {{ data_inicio+' a '+ data_final }} </h3>
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
                  <div class="row" v-if="user.auth.can['relatorio operador']">
                    <div class="col-12 col-md-2">
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

                    <div class="col-12 col-md-2">
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

                    <div class="col-12 col-md-2">
                      <div class="form-group">
                        <label for="">Serviços</label>
                        <select v-model="servico_id" class="form-control">
                          <option value="">TODOS</option>
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

                    <template v-if="user.auth.can['relatorio caixa']">
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
                    </template>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
              <div class="inner">
                <h4>{{ formatValor(0) }}</h4>
                <p>Valor Cash</p>
              </div>
              <div class="icon">
                <i class="ion ion-bag"></i>
              </div>
              <Link href="depositos" class="small-box-footer">Mais detalhe <i class="fas fa-arrow-circle-right"></i></Link>
            </div>
          </div>

          <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
              <div class="inner">
                <h4>{{ formatValor(valor_deposito) }}</h4>
                <p>Total Valor Depositos</p>
              </div>
              <div class="icon">
                <i class="ion ion-bag"></i>
              </div>
              <Link :href="`/depositos?data_inicio=${data_inicio}&data_final=${data_final}&operador=${operador}`" class="small-box-footer">Mais detalhe <i class="fas fa-arrow-circle-right"></i></Link>
            </div>
          </div>

          <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
              <div class="inner">
                <h4>{{ formatValor(totalPagamentos) }}</h4>
                <p>Valor Pagamentos</p>
              </div>
              <div class="icon">
                <i class="ion ion-bag"></i>
              </div>
              <Link :href="`/pagamentos?data_inicio=${data_inicio}&data_final=${data_final}&operador=${operador}`" class="small-box-footer">Mais detalhe <i class="fas fa-arrow-circle-right"></i></Link>
            </div>
          </div>

          <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
              <div class="inner">
                <h4>{{ formatValor(total_arrecadado) }}</h4>
                <p>Total Arrecadado</p>
              </div>
              <div class="icon">
                <i class="ion ion-bag"></i>
              </div>
              <Link :href="`/relatorios/fecho-caixa/operador?data_inicio=${data_inicio}&data_final=${data_final}&operador=${operador}`" class="small-box-footer">Mais detalhe<i class="fas fa-arrow-circle-right"></i></Link>
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
                      <th>Nº Ordem</th>
                      <th>Matricula</th>
                      <th>Nome</th>
                      <th>Nº Factura</th>
                      <th>Nº Pagamento</th>
                      <th>Serviço Pago</th>
                      <th>Curso</th>
                      <th>Data</th>
                      <th class="text-right">Total Pago</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(item, index) in items" :key="item.Codigo">
                      <td>{{ ++index}}</td>
                      <td><Link :href="`/relatorios/extrato-pagamentos?codigo_matricula=${item.matricula}&data_inicio=${data_inicio}&operador=${operador}`" class="small-box-footer">{{ item.matricula }}</Link></td>
                      <td><Link :href="`/relatorios/extrato-pagamentos?codigo_matricula=${item.matricula}&data_inicio=${data_inicio}&operador=${operador}`" class="small-box-footer">{{ item.Nome_Completo }}</Link></td>
                      <td>{{ item.codigo_factura }}</td>
                      <td>{{ item.Codigo }}</td>
                      <td>{{ item.servico!=''?item.servico:item.descricao}}</td>
                      <td>{{ item.curso }}</td>
                      <td>{{ item.DataRegisto }}</td>
                      <td class="text-right">
                        {{ formatValor(item.Totalgeral) }} Kz
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="card-footer">
                <Link href="" class="text-secondary">
                  TOTAL REGISTROS: {{ extratos.total }}
                </Link>
                <Paginacao
                  :links="extratos.links"
                  :prev="extratos.prev_page_url"
                  :next="extratos.next_page_url"
                />
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
  props: [
    "items",
    "ano_lectivos",
    "utilizadores",
    "servicos",
    "valor_deposito",
    "totalPagamentos",
    "total_arrecadado"
  ],
  components: { Link, Paginacao },
  data() {
    return {
      data_inicio: new Date().toISOString().substr(0, 10),
      data_final: new Date().toISOString().substr(0, 10),
      operador: this.$page.props.auth.user.id,
      ano_lectivo: "",
      servico_id: "",
      params: {},
      extratos: [],
    };
  },

  computed: {
    user() {
      return this.$page.props.auth.user;
    },

    utilizadores() {
      const uniqueMap = new Map();
      return this.utilizadores.filter((item) => {
        if (!uniqueMap.has(item.utilizadores.codigo_importado)) {
          uniqueMap.set(item.utilizadores.codigo_importado, true);
          return true;
        }
        return false;
      });
    },

    items() {
      this.extratos=this.items;
      const uniqueMap = new Map();
      if (this.items && this.items.data) {
        return this.items.data.filter((item) => {
          console.log(item.Codigo);
          if (!uniqueMap.has(item.Codigo)) {
            uniqueMap.set(item.Codigo, true);
            return true;
          }
          return false;
        });
      } else {
        return [];
      }
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
    operador: function (val) {
      this.params.operador = val;
      this.updateData();
    },
    servico_id: function (val) {
      this.params.servico_id = val;
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
      this.$inertia.get("/relatorios/fecho-caixa/operador", this.params, {
        preserveState: true,
        preverseScroll: true,
        onSuccess: () => {
          this.$Progress.finish();
        },
      });
    },

    formatValor(atual) {
      const valorFormatado = Intl.NumberFormat("pt-br", {
        style: "currency",
        currency: "AOA",
      }).format(atual);
      return valorFormatado;
    },

    imprimirPDF() {
      window.open(
        `/relatorios/fecho-caixa/operador/pdf?operador=${this.operador}&ano_lectivo=${this.ano_lectivo}&data_inicio=${this.data_inicio}&data_final=${this.data_final}`,
        "_blank"
      );
    },

    imprimirEXCEL() {
      window.open(
        `/relatorios/fecho-caixa/operador/excel?operador=${this.operador}&ano_lectivo=${this.ano_lectivo}&data_inicio=${this.data_inicio}&data_final=${this.data_final}`,
        "_blank"
      );
    },

    voltarPaginaAnterior() {
      window.history.back();
    },
  },
};
</script>
    
    